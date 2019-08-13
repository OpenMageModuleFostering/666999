<?php

class Op_Netbanx_Model_Mysql4_Merchant_Customer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('optimal/merchant_customer');
    }
}
