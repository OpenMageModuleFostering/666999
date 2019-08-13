<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 7/5/11
 * Time: 3:46 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Demac_Optimal_Block_Adminhtml_Risk_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('risk_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('optimal')->__('General'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('optimal')->__('General'),
            'title'     => Mage::helper('optimal')->__('General'),
            'content'   => $this->getLayout()->createBlock('optimal/adminhtml_risk_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}