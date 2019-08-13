<?php

class Demac_Optimal_Block_Adminhtml_Errorcode_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('optimal');
        $code = $this->getRequest()->getParam('code');
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('code' => $code)),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $active = Mage::getModel('optimal/errorcode')->loadByCode($code)->getActive();

        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('errorcode_form', array(
            'legend' => $helper->__('Custom Error Code Mappings')
        ));

        $fieldset->addField('code', 'text', array(
            'label'     => $helper->__('Error Code'),
            'class'     => 'required-entry',
            'required'  => true,
            'comment'   => 'Optimal Error Code',
            'name'      => 'code',
        ));
        $fieldset->addField('active', 'checkbox', array(
            'name'      => 'active',
            'label'     => $helper->__('Is Active?'),
            'title'     => $helper->__('Is Active?'),
            'onclick'   => 'this.value = this.checked ? 1 : 0',
            'values'    => array(0, 1),
            'checked'   => $active ? 'true' : ''
        ));
        $fieldset->addField('message', 'editor', array(
            'name'      => 'message',
            'label'     => $helper->__('Default Message'),
            'title'     => $helper->__('Default Message'),
            'style'     => 'width:500px; height:150px;',
            'wysiwyg'   => false,
            'required'  => true,
        ));
        $fieldset->addField('custom_message', 'editor', array(
            'name'      => 'custom_message',
            'label'     => $helper->__('Custom Message'),
            'title'     => $helper->__('Custom Message'),
            'style'     => 'width:500px; height:150px;',
            'wysiwyg'   => false,
            'required'  => false,
        ));

        $action = 'add';

        if (Mage::registry('errorcode_data')) {
            $action = 'edit';
            $form->setValues(Mage::registry('errorcode_data')->getData());
        }

        $fieldset->addField('action', 'hidden', array(
            'name' => 'action',
            'value' => $action
        ));

        return parent::_prepareForm();
    }
}