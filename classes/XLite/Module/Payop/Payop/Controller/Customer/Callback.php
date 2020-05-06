<?php

namespace XLite\Module\Payop\Payop\Controller\Customer;

/**
 * Class Callback
 *
 * @package XLite\Module\Payop\Payop\Controller\Customer
 */
class Callback extends \XLite\Controller\Customer\Callback implements \XLite\Base\IDecorator
{

    /**
     * PayOp Callback
     *
     * @throws \Exception
     */
    protected function doActionCallback()
    {
        $txn = file_get_contents('php://input');
        $txn = json_decode($txn);
        $txnIdName = $txn->transaction->order->id;
        if (!empty($txnIdName)) {
            $transaction = \XLite\Core\Database::getRepo('XLite\Model\Payment\Transaction')
              ->findOneByPublicTxnId($txnIdName);
        }
        if ($transaction) {
            $this->transaction = $transaction;

            try {
                $transaction->getPaymentMethod()->getProcessor()->processCallback($transaction);
                // because tryClose might refresh $transaction and it will lose its status
                \XLite\Core\Database::getEM()->flush();

                $cart = $transaction->getOrder();
                if ($cart instanceof \XLite\Model\Cart) {
                    $cart->tryClose();
                }

                $transaction->getOrder()->setPaymentStatusByTransaction($transaction);
                $transaction->getOrder()->update();

                \XLite\Core\Database::getEM()->flush();
            } catch (CallbackNotReady $e) {
                $message = $e->getMessage()
                  ?: 'Not ready to process this callback right now. TXN ID: ' . $transaction->getPublicTxnId();

                $processor = $transaction->getPaymentMethod()->getProcessor();

                if ($processor instanceof Online) {
                    $this->setSuppressOutput(true);
                    $this->set('silent', true);

                    $processor->markCallbackRequestAsInvalid($message);
                    $processor->processCallbackNotReady($transaction);
                }
            } catch (ACallbackException $e) {
                $processor = $transaction->getPaymentMethod()->getProcessor();
                parent::doActionCallback();
                if ($e->getMessage() && $processor instanceof Online) {
                    if ($processor instanceof Online) {
                        $processor->markCallbackRequestAsInvalid($e->getMessage());
                    } else {
                        \XLite\Logger::getInstance()->log($e->getMessage(), LOG_WARNING);
                    }
                }
            }
        } else {
            \XLite\Logger::getInstance()->log(
              'Request callback with undefined payment transaction' . PHP_EOL
              . 'Data: ' . var_export(\XLite\Core\Request::getInstance()->getData(), true),
              LOG_ERR
            );
        }

        $this->set('silent', true);
    }

}
