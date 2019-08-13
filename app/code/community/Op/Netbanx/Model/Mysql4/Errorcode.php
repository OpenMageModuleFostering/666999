<?php
/**
 * Created by Self.
 * User: Sohaib Muneer - Magento Developer <smuneer@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 10/21/14
 * Time: 11:36 AM
 */

class Op_Netbanx_Model_Mysql4_Errorcode extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = false;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('optimal/errorcode', 'code');
    }

    public function loadByCode(Op_Netbanx_Model_Errorcode $object, $code)
    {
        $adapter    = $this->_getReadAdapter();
        $where      = $adapter->quoteInto("code = ?", $code);
        $select     = $adapter->select()
                        ->from($this->getMainTable())
                        ->where($where);

        if ($data = $adapter->fetchRow($select)) {
            $object->setData($data);
            $this->_afterLoad($object);
        }

        return $this;
    }
}