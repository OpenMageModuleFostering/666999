<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amacgregor
 * Date: 7/5/11
 * Time: 3:41 PM
 * To change this template use File | Settings | File Templates.
 */

class Demac_Optimal_Block_Adminhtml_Risk_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('riskGrid');
        // This is the primary key of the database
        $this->setDefaultSort('desc_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('optimal/risk')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('optimal')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'entity_id',
        ));

        $this->addColumn('risk_code', array(
            'header'    => Mage::helper('optimal')->__('Risk Error Code '),
            'align'     =>'left',
            'width'     => '150px',
            'index'     => 'risk_code',
        ));


        $this->addColumn('description', array(
            'header'    => Mage::helper('optimal')->__('Description'),
            'index'     => 'description',
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('optimal')->__('Magento Order Status'),
            'index'     => 'status',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }


}