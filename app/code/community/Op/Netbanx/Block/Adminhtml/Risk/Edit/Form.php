<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 7/5/11
 * Time: 3:45 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Op_Netbanx_Block_Adminhtml_Risk_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}