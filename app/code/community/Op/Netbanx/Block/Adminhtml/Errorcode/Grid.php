<?php

class Op_Netbanx_Block_Adminhtml_Errorcode_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('optimal/errorcode_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'code' => $row->getId()
        ));
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('optimal');

        $this->addColumn('code', array(
            'header'    => $helper->__('Code'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'code',
        ))
        ->addColumn('message', array(
            'header'    => $helper->__('Default Message'),
            'align'     => 'left',
            'width'     => '35%',
            'index'     => 'message',
        ))
        ->addColumn('custom_message', array(
            'header'    => $helper->__('Custom Message'),
            'align'     => 'left',
            'width'     => '35%',
            'index'     => 'custom_message'
        ))
        ->addColumn('active', array(
            'header'    => $helper->__('Active'),
            'align'     => 'left',
            'index'     => 'active',
            'type'      => 'options',
            'options'   => $this->_yesNoArray()
        ));

        return parent::_prepareColumns();
    }

    protected function _yesNoArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('No'),
            1 => Mage::helper('adminhtml')->__('Yes'),
        );
    }

}