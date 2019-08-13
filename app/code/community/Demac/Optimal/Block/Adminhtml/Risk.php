<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 9/11/13
 * Time: 3:30 PM
 */ 
class Demac_Optimal_Block_Adminhtml_Risk  extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_risk';
        $this->_blockGroup = 'optimal';
        $this->_headerText = Mage::helper('optimal')->__('Risk Error Codes Manager');
        $this->_addButtonLabel = Mage::helper('optimal')->__('Add Mapping');
        parent::__construct();
    }
}