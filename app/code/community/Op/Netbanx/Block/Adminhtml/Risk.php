<?php

class Op_Netbanx_Block_Adminhtml_Risk  extends Mage_Adminhtml_Block_Widget_Grid_Container
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