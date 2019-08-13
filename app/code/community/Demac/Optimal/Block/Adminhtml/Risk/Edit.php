<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 7/5/11
 * Time: 3:39 PM
 * To change this template use File | Settings | File Templates.
 */

class Demac_Optimal_Block_Adminhtml_Risk_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'optimal';
        $this->_controller = 'adminhtml_risk';

        $this->_updateButton('save', 'label', Mage::helper('optimal')->__('Save Mapping'));
        $this->_updateButton('delete', 'label', Mage::helper('optimal')->__('Delete Mapping'));
    }

    /**
     * Get header text
     *
     * @return mixed
     */
    public function getHeaderText()
    {
        if( Mage::registry('mapping_data') && Mage::registry('mapping_data')->getId() ) {
            return Mage::helper('optimal')->__("Edit Mapping '%s'", $this->htmlEscape(Mage::registry('mapping_data')->getTitle()));
        } else {
            return Mage::helper('optimal')->__('Add Mapping');
        }
    }
}