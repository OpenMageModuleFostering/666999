<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 12/09/13
 * Time: 9:12 AM
 * To change this template use File | Settings | File Templates.
 */

class Demac_Optimal_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('optimal/profile');
    }
}
