<?php

class Demac_Optimal_Model_Profile extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        parent::_construct();
        $this->_init('optimal/profile');
    }

    /**
     * @param $customerId
     * @return $this
     */
    public function loadByCustomerId($customerId)
    {
        $this->_getResource()->loadByCustomerId($this, $customerId);
        return $this;
    }

    public function loadByProfileAndToken($profileId, $paymentToken)
    {
        $this->_getResource()->loadByProfileAndToken($this, $profileId, $paymentToken);
        return $this;

    }

}