<?php

class Op_Netbanx_Adminhtml_ErrorcodesController extends Mage_Adminhtml_Controller_Action
{
    public function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/optimal/mapping')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Error Codes Manager'), Mage::helper('adminhtml')->__('Error Codes Manager'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('optimal/adminhtml_errorcode'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $code = $this->getRequest()->getParam('code');
        $model  = Mage::getModel('optimal/errorcode')->load($code);

        if (!($model->getCode() || empty($code))) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('optimal')->__('Mapping does not exist'));
            $this->_redirect('*/*/');
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        Mage::register('errorcode_data', $model);

        $this->loadLayout();
        $this->_setActiveMenu('system/optimal');

        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Error Codes Manager'), Mage::helper('adminhtml')->__('Error Codes Manager'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Add Mapping'), Mage::helper('adminhtml')->__('Add Mapping'));

        $this->_addContent($this->getLayout()->createBlock('optimal/adminhtml_errorcode_edit'));

        $this->renderLayout();
    }

    public function saveAction()
    {
        if (!($postData = $this->getRequest()->getPost())) {
            $this->_redirect('*/*/');
            return;
        }

        $code = $this->getRequest()->getParam('code'); // this picks up the code from $_REQUEST
        $model = Mage::getModel('optimal/errorcode');
        $model->load($code);

        try {
            $model->setData(array(
                'code' => $code,
                'message' => $postData['message'],
                'custom_message' => $postData['custom_message'],
                'active' => isset($postData['active']) ? 1 : 0
            ))->save();

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully saved'));
            Mage::getSingleton('adminhtml/session')->setDescriptionData(false);

            $this->_redirect('*/*/');
            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError('That was an Exception Message');
            Mage::getSingleton('adminhtml/session')->setDescriptionData($this->getRequest()->getPost());
            $this->_redirect('*/*/edit', array('code' => $this->getRequest()->getParam('code')));
            return;
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