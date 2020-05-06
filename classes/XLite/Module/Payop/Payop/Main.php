<?php
namespace XLite\Module\Payop\Payop;

abstract class Main extends \XLite\Module\AModule
{
    /**
     * Author name
     *
     * @return string
     */
    public static function getAuthorName()
    {
        return 'PayOp';
    }

    /**
     * Module name
     *
     * @return string
     */
    public static function getModuleName()
    {
        return 'PayOp';
    }

    /**
     * Get module major version
     *
     * @return string
     */
    public static function getMajorVersion()
    {
        return '5.4';
    }

    /**
     * Module version
     *
     * @return string
     */
    public static function getMinorVersion()
    {
        return 0;
    }

    /**
     * Module description
     *
     * @return string
     */
    public static function getDescription()
    {
        return 'Enables receiving payments for your online store via PayOp';
    }

    public static function showSettingsForm()
    {
        return true;
    }
}