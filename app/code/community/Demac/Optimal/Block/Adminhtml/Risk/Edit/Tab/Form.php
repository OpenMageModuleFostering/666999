<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 7/5/11
 * Time: 3:48 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Demac_Optimal_Block_Adminhtml_Risk_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('risk_form', array('legend'=>Mage::helper('optimal')->__('Risk Error - Order Status Mapping')));

        $orderStatusCollection = Mage::getModel('sales/order_status')->getResourceCollection();
        $statuses = array();
        foreach($orderStatusCollection as $status)
        {
            $statuses[] = array(
                'value' => $status->getStatus(),
                'label' => Mage::helper('catalog')->__($status->getLabel())
            );

        }

        $fieldset->addField('risk_code', 'text', array(
            'label'     => Mage::helper('optimal')->__('Risk Code'),
            'class'     => 'required-entry',
            'required'  => true,
            'comment'   => 'The ThreatMetrix error code',
            'name'      => 'risk_code',
        ));

        $fieldset->addField('description', 'editor', array(
            'name'      => 'description',
            'label'     => Mage::helper('optimal')->__('Description'),
            'title'     => Mage::helper('optimal')->__('Description'),
            'style'     => 'width:500px; height:400px;',
            'wysiwyg'   => false,
            'required'  => true,
        ));


        $fieldset->addField('status', 'select', array(
            'name'      => 'status',
            'label'     => Mage::helper('optimal')->__('Magento Order Status'),
            'title'     => Mage::helper('optimal')->__('Magento Order Status'),
            'style'     => 'width:100px;',
            'values'    => $statuses,
            'value'     => 0,
        ));

        //Mage::log(Mage::getSingleton('adminhtml/session'));
        if ( Mage::getSingleton('adminhtml/session')->getRiskData() )
        {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getRiskData());
            Mage::getSingleton('adminhtml/session')->setRiskData(null);


        } elseif ( Mage::registry('mapping_data') ) {
            $form->setValues(Mage::registry('mapping_data')->getData());
        }

        return parent::_prepareForm();
    }

}