<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 12/09/13
 * Time: 9:12 AM
 * To change this template use File | Settings | File Templates.
 */

class Op_Netbanx_Model_Mysql4_Risk extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_isPkAutoIncrement = true;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('optimal/risk', 'entity_id');
    }

    public function loadByCode(Op_Netbanx_Model_Risk $object, $errorCode)
    {
        $adapter    = $this->_getReadAdapter();
        $where      = $adapter->quoteInto("risk_code = ?", $errorCode);

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
