<?php

namespace XLite\Module\Payop\Payop\Model\Payment\Processor;

/**
 * Class Payop
 *
 * @package XLite\Module\Payop\Payop\Model\Payment\Processor
 */
class Payop extends \XLite\Model\Payment\Base\WebBased
{
    protected $log_name = 'payop_log';
    protected $checkoutUrl = null;

    /**
     * Get checkout url
     *
     * @return string
     */
    protected function getFormURL()
    {
        if (!isset($this->checkoutURL)){
            $payopSettings = $this->getPayopSettings();
            $invoiceId = $this->createInvoice($payopSettings);
            $this->checkoutUrl = 'https://payop.com/'.'en'.'/payment/invoice-preprocessing/'.$invoiceId;
        }
        return $this->checkoutUrl;
    }

    /**
     * Create invoice request
     *
     * @param $requestBody
     *
     * @return string
     */
    protected function createInvoice($requestBody)
    {
        $url = 'https://payop.com/v1/invoices/create';
        $requestBody = json_encode($requestBody);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($result, 0, $headerSize);
        $headers = explode("\r\n", $headers);
        $invoiceIdentifier = preg_grep("/^identifier/", $headers);
        $invoiceIdentifier = implode(',', $invoiceIdentifier);
        $invoiceIdentifier = substr($invoiceIdentifier, strrpos($invoiceIdentifier, ':')+2);
        curl_close($ch);
        if (!$invoiceIdentifier) {
            \XLite\Logger::logCustom($this->log_name, 'Invoice wasn\'t created.');
            \XLite\Logger::logCustom($this->log_name, var_export($result));
        }
        return $invoiceIdentifier;
    }

    /**
     * Get settings template
     *
     * @return string
     */
    public function getSettingsWidget()
    {
        return 'modules/Payop/Payop/config.twig';
    }

    /**
     * Check configuration
     *
     * @param \XLite\Model\Payment\Method $method
     *
     * @return bool
     */
    public function isConfigured(\XLite\Model\Payment\Method $method)
    {
        return parent::isConfigured($method)
          && $method->getSetting('publickey')
          && $method->getSetting('secretkey')
          && $method->getSetting('jwttoken')
          && $method->getSetting('language');
    }

    /**
     * Get settings
     *
     * @return array
     */
    protected function getPayopSettings()
    {
        return array(
            'publicKey' => $this->getSetting('publickey'),
            'secretKey' => $this->getSetting('secretkey'),
            'order' => [
                'id' => $this->transaction->getPublicTxnId(),
                'amount' => $this->transaction->getValue(),
                'currency' => $this->transaction->getCurrency()->getCode(),
                'description' => $this->transaction->getOrder()->getDescription(),
                'items' => $this->transaction->getOrder()->getItems()
            ],
            'payer' => [
                'email' => $this->transaction->getOrigProfile()->getEmail(),
                'name' => $this->transaction->getOrigProfile()->getName()
            ],
            'language' => $this->getSetting('language'),
            'resultUrl' => $this->getReturnURL(false, true),
            'failPath' => $this->getReturnURL(false, true),
            'signature' => $this->generateSignature(
                $this->transaction->getPublicTxnId(),
                $this->transaction->getValue(),
                $this->transaction->getCurrency()->getCode(),
                $this->getSetting('secretkey')
            )
        );
    }

    protected function getFormFields()
    {
        return array(
          'transactionID' => $this->transaction->getPublicTxnId(),
          'returnURL' => $this->getReturnURL('transactionID'),
        );
    }

    protected function getFormMethod()
    {
        return self::FORM_METHOD_GET;
    }

    /**
     * Process PayOp callback
     *
     * @param \XLite\Model\Payment\Transaction $transaction
     */
    public function processCallback(\XLite\Model\Payment\Transaction $transaction) {
        $request = file_get_contents('php://input');
        $request = json_decode($request);
        $status = $request->transaction->state;
        $txid = $request->invoice->txid;
        $errorCode = $request->transaction->error->code;
        $errorMessage = $request->transaction->error->message;
        if (!$this->validateCallback($txid, $status, $transaction)) {
            parent::processCallback($transaction);
        } else {
            if (2 == $status) {
                $transaction->setStatus($transaction::STATUS_SUCCESS);
            } elseif (5 == $status) {
                if ('' != $errorCode){
                    $transaction->setNote('Transaction failed. Reason: ' . $errorCode . ' : ' . $errorMessage);
                    \XLite\Logger::logCustom($this->log_name, 'Transaction '. $txid .' failed. Reason: ' . $errorCode . ' : ' . $errorMessage ) ;
                }
                $transaction->setStatus($transaction::STATUS_FAILED);
            }
        }
    }

    /**
     * Check callback validity
     *
     * @param                                  $txid
     * @param                                  $status
     * @param \XLite\Model\Payment\Transaction $transaction
     *
     * @return bool|null
     */
    protected function validateCallback($txid, $status, \XLite\Model\Payment\Transaction $transaction)
    {
        if (!$txid){
            return null;
        }
        $jwtToken = $transaction->getPaymentMethod()->getSetting('jwttoken');
        $url = 'https://payop.com/v1/transactions/'.$txid;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'Authorization: Bearer '. $jwtToken));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);
        if ($result->data) {
            if ($result->data->productAmount != $transaction->getValue()) {
                return null;
            }
            if ($result->data->currency != $transaction->getCurrency()->getCode()) {
                return null;
            }
            if ($result->data->state != $status) {
                return null;
            }
            return true;
        } else {
            return null;
        }
    }

    /**
     * Process return
     *
     * @param \XLite\Model\Payment\Transaction $transaction
     */
    public function processReturn(\XLite\Model\Payment\Transaction $transaction)
    {
        parent::processReturn($transaction);
        $status = $transaction::STATUS_PENDING;
        $this->transaction->setStatus($status);
    }

    /**
     * Invoice generation
     *
     * @param $orderId
     * @param $amount
     * @param $currency
     * @param $secretKey
     *
     * @return string
     */
    private function generateSignature($orderId, $amount, $currency, $secretKey)
    {
        $sign_str = ['id' => $orderId, 'amount' => $amount, 'currency' => $currency];
        ksort($sign_str, SORT_STRING);
        $sign_data = array_values($sign_str);
        array_push($sign_data, $secretKey);
        return hash('sha256', implode(':', $sign_data));
    }
}