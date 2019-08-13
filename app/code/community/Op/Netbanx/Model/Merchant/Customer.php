<?php

class Op_Netbanx_Model_Merchant_Customer extends Mage_Core_Model_Abstract
{

    // TODO when changed ensure to update this
    const TABLE_NAME = 'demac_optimal_merchant_customer';

    public function __construct()
    {
        parent::_construct();
        $this->_init('optimal/merchant_customer');
    }

}



