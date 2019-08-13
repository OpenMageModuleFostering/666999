<?php

class Op_Netbanx_Block_Adminhtml_Errorcode_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'code';
        $this->_blockGroup = 'optimal';
        $this->_controller = 'adminhtml_errorcode';

        $this->_updateButton('save', 'label', Mage::helper('optimal')->__('Save Error Code Mapping'));
        $this->_updateButton('delete', 'label', Mage::helper('optimal')->__('Delete Error Code Mapping'));
    }

    public function getHeaderText()
    {
        if (Mage::registry('errorcode_data') && Mage::registry('errorcode_data')->getCode()) {
            return Mage::helper('optimal')->__("Edit Mapping '%s'", $this->htmlEscape(Mage::registry('errorcode_data')->getCode()));
        } else {
            return Mage::helper('optimal')->__('Add Error Code Mapping');
        }
    }
}