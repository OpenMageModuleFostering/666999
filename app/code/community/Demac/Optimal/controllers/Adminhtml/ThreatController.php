<?php

class Demac_Optimal_Adminhtml_ThreatController extends Mage_Adminhtml_Controller_Action
{
    public function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/optimal/mapping')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Risk Code Status Manager'), Mage::helper('adminhtml')->__('Risk Code Status Manager'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('optimal/adminhtml_risk'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $modelId     = $this->getRequest()->getParam('id');
        $model  = Mage::getModel('optimal/risk')->load($modelId);

        if ($model->getId() || $modelId == 0) {

            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('mapping_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('system/optimal');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Risk Error Codes Manager'), Mage::helper('adminhtml')->__('Risk Error Codes Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Add Mapping'), Mage::helper('adminhtml')->__('Add Mapping'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
                $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
            }

            $this->_addContent($this->getLayout()->createBlock('optimal/adminhtml_risk_edit'))
                ->_addLeft($this->getLayout()->createBlock('optimal/adminhtml_risk_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('optimal')->__('Mapping does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ( $this->getRequest()->getPost() ) {
            try {
                $postData = $this->getRequest()->getPost();
                $model = Mage::getModel('optimal/risk');

                $model->setId($this->getRequest()->getParam('id'))
                    ->setDescription($postData['description'])
                    ->setRiskCode($postData['risk_code'])
                    ->setStatus($postData['status'])
                    ->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setDescriptionData(false);

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setDescriptionData($this->getRequest()->getPost());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $model = Mage::getModel('optimal/risk');

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

}