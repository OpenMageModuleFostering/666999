<?php
/**
 * Created by PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 3/10/14
 * Time: 11:03 AM
 */

class Op_Netbanx_Model_Mysql4_Creditcard extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = true;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('optimal/creditcard', 'entity_id');
    }

    public function loadByProfileId(Op_Netbanx_Model_Creditcard $object, $profileId)
    {
        $adapter    = $this->_getReadAdapter();
        $where      = $adapter->quoteInto("profile_id = ?", $profileId);

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


    public function loadByProfileAndToken(Op_Netbanx_Model_Creditcard $object, $profileId, $paymentToken)
    {
        $adapter        = $this->_getReadAdapter();
        $whereProfile   = $adapter->quoteInto("profile_id = ?", $profileId);
        $whereToken     = $adapter->quoteInto("payment_token = ?", $paymentToken);

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

    public function loadByMerchantCustomerId(Op_Netbanx_Model_Creditcard $object, $merchantCustomerId)
    {
        $adapter    = $this->_getReadAdapter();
        $where      = $adapter->quoteInto("merchant_customer_id = ?", $merchantCustomerId);

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
}
