<?php

class Op_Netbanx_Block_Adminhtml_Errorcode extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_errorcode';
        $this->_blockGroup = 'optimal';
        $this->_headerText = Mage::helper('optimal')->__('Error Codes Manager');
    }

    public function getCreateUrl()
    {
        return $this->getUrl('adminhtml/errorcodes/edit');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_removeButton('add');
    }
}