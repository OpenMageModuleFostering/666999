<?php

class Demac_Optimal_Model_Merchant_Customer extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        parent::_construct();
        $this->_init('optimal/merchant_customer');
    }
}