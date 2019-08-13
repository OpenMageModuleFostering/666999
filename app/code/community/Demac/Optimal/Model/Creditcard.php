<?php

class Demac_Optimal_Model_Creditcard extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        parent::_construct();
        $this->_init('optimal/creditcard');
    }

    public function loadByProfileId($profileId)
    {
        $this->_getResource()->loadByProfileId($this, $profileId);
        return $this;
    }

    public function loadByProfileAndToken($profileId, $paymentToken)
    {
        $this->_getResource()->loadByProfileAndToken($this, $profileId, $paymentToken);
        return $this;

    }

    public function loadByMerchantCustomerId($merchantCustomerId)
    {
        $this->_getResource()->loadByMerchantCustomerId($this, $merchantCustomerId);
        return $this;

    }
}