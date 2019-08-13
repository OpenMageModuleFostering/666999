<?php

class Op_Netbanx_Model_Mysql4_Merchant_Customer extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = true;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('optimal/merchant_customer', 'merchant_customer_id');
    }

}