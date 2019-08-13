<?php

class Op_Netbanx_Model_Config_Mode extends Mage_Core_Model_Config_Data
{
    const DEV_VALUE = 'development';
    const PROD_VALUE = 'production';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            self::DEV_VALUE => Mage::helper('optimal')->__('Development'),
            self::PROD_VALUE => Mage::helper('optimal')->__('Production'),
        );
    }
}