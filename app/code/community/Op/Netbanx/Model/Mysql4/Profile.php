<?php

class Op_Netbanx_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = true;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('optimal/profile', 'entity_id');
    }

    public function loadByCustomerId(Op_Netbanx_Model_Profile $object, $customerId)
    {
        $adapter    = $this->_getReadAdapter();
        $where      = $adapter->quoteInto("customer_id = ?", $customerId);

        $select     = $adapter->select()
            ->from($this->getMainTable())
            ->where($where);
        if($data = $adapter->fetchRow($select))
        {
            $object->setData($data);
            $this->_afterLoad($object);
        }
        return $this;

    }

    public function loadByProfileAndToken(Op_Netbanx_Model_Profile $object, $profileId, $paymentToken)
    {
        $adapter    = $this->_getReadAdapter();
        $whereProfile      = $adapter->quoteInto("profile_id = ?", $profileId);
        $whereToken     =    $adapter->quoteInto("payment_token = ?", $paymentToken);

        $select     = $adapter->select()
            ->from($this->getMainTable())
            ->where($whereProfile)
            ->where($whereToken);
        if($data = $adapter->fetchRow($select))
        {
            $object->setData($data);
            $this->_afterLoad($object);
        }
        return $this;

    }
}