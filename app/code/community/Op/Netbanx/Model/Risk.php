<?php

class Op_Netbanx_Model_Risk extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        parent::_construct();
        $this->_init('optimal/risk');
    }

    /**
     * @param $manifestId
     * @return $this
     */
    public function loadByCode($errorCode)
    {
        $this->_getResource()->loadByCode($this, $errorCode);
        return $this;
    }
}