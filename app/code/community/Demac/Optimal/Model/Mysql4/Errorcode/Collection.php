<?php

class Demac_Optimal_Model_Mysql4_Errorcode_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('optimal/errorcode');
    }
}