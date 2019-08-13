<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Customer
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customer account controller
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Op_Netbanx_Frontend_OptimalController extends Mage_Core_Controller_Front_Action
{
    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        // a brute-force protection here would be nice

        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = $this->getRequest()->getActionName();
        $openActions = array(
            'view'
        );
        $pattern = '/^(' . implode('|', $openActions) . ')/i';

        if (!preg_match($pattern, $action)) {
            if (!$this->_getSession()->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    }

    /**
     * Action postdispatch
     *
     * Remove No-referer flag from customer session after each action
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->_getSession()->unsNoReferer(false);
    }

    /**
     * Default customer account page
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('optimal/customer_cards')
        );
        $this->getLayout()->getBlock('head')->setTitle($this->__('Manage Credit Cards'));
        $this->renderLayout();
    }

    /**
     *
     */
    public function deleteAction()
    {
        $params = $this->getRequest()->getParams();
        $referrerUrl = $this->_getRefererUrl();

        if($params['profile_id'])
        {
            /* Disable actual card deletion on the netbanks end
             *
             * $client = Mage::getModel('optimal/profile_client');
             * $result = $client->deleteProfile((int)$params['profile_id']);
             */

            $profile = Mage::getModel('optimal/creditcard')->load($params['profile_id']);
            if($profile->getId())
            {
                $profile->setIsDeleted(true);
                $profile->save();
                Mage::getSingleton('customer/session')->addSuccess('Card Deleted');

            }else {
                Mage::getSingleton('customer/session')->addError('There was a problem deleting your card');
            }

            $this->_redirectReferer($referrerUrl);
        }
    }

    /**
     *
     */
    public function addPostAction()
    {
        $referrerUrl = $this->_getRefererUrl();
        try {
            $session    = Mage::getSingleton('customer/session');
            $client     = Mage::getModel('optimal/hosted_client');
            $helper     = Mage::helper('optimal');
            $customer   = $session->getCustomer();
            $params     = $this->getRequest()->getPost();

            $customerData = $customer->getData();


            $createProfile = true;

            $orderData      = array();

            // Get order data
            $orderData['remote_ip']         = Mage::helper('core/http')->getRemoteAddr(true);
            $orderData['order_items']       = null;
            $orderData['increment_id']      = 0;
            $orderData['customer_email']    = $customerData['email'];


            // Start [Refactor] : Pull the order billing and shipping address rather than the customer default address.
            $billingAddressId = $customer->getDefaultBilling();
            if($billingAddressId) {
                $orderData['billing_address'] = Mage::getModel('customer/address')->load($billingAddressId);
            } else {
                Mage::throwException($this->__("There was a problem adding the credit card. You must create a billing address first."));
            }

            $shippingAddressId = $customer->getDefaultShipping();
            if($shippingAddressId) {
                $orderData['shipping_address'] = Mage::getModel('customer/address')->load($shippingAddressId);
            } else {
                $orderData['shipping_address'] = $orderData['billing_address'];
            }
            // End Refactor

            $orderData['base_tax_amount']               = 0;
            $orderData['base_grand_total']              = 0;
            $orderData['base_currency_code']            = 'CAD';
            $orderData['base_shipping_amount']          = 0;
            $orderData['base_discount_amount']          = 0;
            $orderData['base_customer_balance_amount']  = 0;

            // Get Customer Information
            $customerData['is_guest'] = false;
            $customerData['lastname'] = (string)$orderData['billing_address']->getLastname();
            $customerData['firstname'] = (string)$orderData['billing_address']->getFirstname();


            // Call the helper and get the data for netbanks
            $data = $helper->prepareNetbanksOrderData($orderData ,$customerData, $createProfile, 'authorize');

            foreach ($data['extendedOptions'] as $index => $xOp) {
                if ($xOp['key'] == 'skip3D') {
                    $data['extendedOptions'][$index]['value'] = true;
                }
            }

            // Call Netbanks API and create the order
            $response = $client->createOrder($data);
            if (isset($response->link)) {
                foreach ($response->link as $link) {
                    if($link->rel === 'hosted_payment') {
                        $postURL = $link->uri;
                    }
                }
            } else {
                Mage::throwException($this->__("There was a problem adding your credit card."));
            }

            if(isset($postURL)){

                $paymentData = array(
                    'cardNum'               => (string) $params['cc_number'],
                    'cardExpiryMonth'       => (int) $params['cc_exp_month'],
                    'cardExpiryYear'        => (int) $params['cc_exp_year'],
                    'cvdNumber'             => (string) $params['cc_cid'],
                    'storeCardIndicator'    => true
                );

                $paymentResponse    = $client->submitPayment($postURL,$paymentData);
                $orderStatus        = $client->retrieveOrder($response->id);
                $transaction        = $orderStatus->transaction;

                // Check the order status for the profile information and try to save it
                if($createProfile){
                    if(isset($orderStatus->profile)){
                        $profile = Mage::getModel('optimal/creditcard');
                        $merchantCustomerId = $orderStatus->profile->merchantCustomerId;

                        if(!isset($merchantCustomerId))
                        {
                            $merchantCustomerId = Mage::helper('optimal')->getMerchantCustomerId($session->getId());
                            $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];
                        }

                        // Set Profile Info
                        $profile->setCustomerId($session->getId());
                        $profile->setProfileId($orderStatus->profile->id);
                        $profile->setMerchantCustomerId($merchantCustomerId);
                        $profile->setPaymentToken($orderStatus->profile->paymentToken);

                        // Set Nickname
                        $cardName = $orderStatus->transaction->card->brand;
                        $profile->setCardNickname(Mage::helper('optimal')->processCardNickname($cardName));

                        // Start [Refactor] : This is pulling the billing first name / last name instead of the name entered when creating the credit card.
                        $cardHolder = $customerData['firstname'] . ' ' . $customerData['lastname'];
                        // End Refactor
                        $profile->setCardHolder($cardHolder);

                        // Set Card Info
                        $lastfour = substr($params['cc_number'],-4);
                        $profile->setLastFourDigits($lastfour);

                        // Format card expiration date [todo]: Make a helper function
                        $expirationDate = sprintf("%02s", $paymentData['cardExpiryMonth']) . "/"  . substr($paymentData['cardExpiryYear'], -2);

                        $profile->setCardExpiration($expirationDate);

                        $profile->save();

                    }else {
                        Mage::throwException($this->__("There was a problem saving your payment information."));
                    }
                }
            }
        } catch (Mage_Core_Exception $exception) {
            Mage::getSingleton('core/session')->addError($exception->getMessage());
        }
        $this->_redirectReferer($referrerUrl);
    }
}
