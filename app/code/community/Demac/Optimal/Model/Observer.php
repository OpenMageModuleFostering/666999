<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Head Developer <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/24/13
 * Time: 1:49 PM
 */

class Demac_Optimal_Model_Observer
{
    /**
     *
     * Before we actually submit an order we need to:
     *  1- Create a netbanks order using their IP
     *  2- Store the response inside the order object
     *  3- Get the payment information entered by the customer
     *  4- Post that directly to the callback url that we got from step 1
     *  5- Handle the response from the silentpost form
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPlaceBefore(Varien_Event_Observer $observer)
    {
        $order      = $observer->getOrder();
        $payment    = $order->getPayment();
        $postURL    = null;

        if($payment->getMethod() == Demac_Optimal_Model_Method_Hosted::METHOD_CODE)
        {
            $client = Mage::getModel('optimal/hosted_client');
            $helper = Mage::helper('optimal');

            $billingAddress     = $order->getBillingAddress();
            $shippingAddress    = $order->getShippingAddress();
            $shoppingCartArray  = array();
            $orderItems         = $order->getAllVisibleItems();

            // Minimum order information needed
            $data = array(
                'merchantRefNum'            => (string) $order->getIncrementId() . '-' . time(),
                'currencyCode'              => (string) $order->getBaseCurrencyCode(),
                'totalAmount'               => (int) $helper->formatAmount($order->getBaseGrandTotal()),
                'customerNotificationEmail' => (string) $order->getCustomerEmail(),
                'merchantNotificationEmail' => 'allan@demacmedia.com',
            );

            // Customer Profile information
            $customer = Mage::getSingleton('customer/session');
            $customerProfileArray = array(
                'merchantCustomerId'    => (string) $order->getRemoteIp(),
            );

            if($customer->isLoggedIn()) {
                $customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
                $customerProfile['firstName']   = (string) $customerData['firstname'];
                $customerProfile['lastName']    = (string) $customerData['lastname'];;
            } else {
                $customerProfile['firstName']   = (string) 'Guest';
                $customerProfile['lastName']    = (string) 'Customer';
            }

            // Order extended options
            $extendedOptionsArray = array();

            // Need to be sure this matches the store on which the order was placed
            $transactionMode =  Mage::getStoreConfig('payment/optimal_hosted/payment_action');

            switch($transactionMode){
                case Demac_Optimal_Model_Method_Hosted::ACTION_AUTHORIZE:
                    $extendedOptionsArray[] = array(
                        'key'       => (string) 'authType',
                        'value'     => (string) Demac_Optimal_Model_Config_Transaction::AUTH_VALUE
                    );
                    break;
                case Demac_Optimal_Model_Method_Hosted::ACTION_AUTHORIZE_CAPTURE:
                    $extendedOptionsArray[] = array(
                        'key'       => (string) 'authType',
                        'value'     => (string) Demac_Optimal_Model_Config_Transaction::CAPT_VALUE
                    );
                    break;
                default:
                    Mage::throwException($this->__("There is no transaction method set, please contact the website administrator."));
                    break;
            }

            $skip3d = Mage::getStoreConfig('payment/optimal_hosted/skip3d');

            if($skip3d)
            {
                $extendedOptionsArray[] = array(
                    'key'       => (string) 'skip3D',
                    'value'     => true
                );
            }else {
                $extendedOptionsArray[] = array(
                    'key'       => (string) 'skip3D',
                    'value'     => false
                );
            }

            $addendumDataArray = array();
            $threatMetrixId = Mage::getSingleton('core/session')->getThreatMetrixSessionKey();

            if(isset($threatMetrixId) && Mage::getStoreConfig('payment/threat_metrix/active'))
            {
                $extendedOptionsArray[] = array(
                    'key'       => (string) 'threatMetrixSessionId',
                    'value'     => $threatMetrixId
                );
            }

            // Ancillary fees information
            $ancillaryFeesArray = array(
                array(
                    'amount'        => (int)$helper->formatAmount($order->getBaseShippingAmount()),
                    'description'   => "Shipping Amount"
                ),
                array(
                    'amount'        => (int)$helper->formatAmount($order->getBaseTaxAmount()),
                    'description'   => "Tax Amount"
                ),
                array(
                    'amount'        => (int)$helper->formatAmount($order->getBaseDiscountAmount()),
                    'description'   => "Discount Amount"
                ),
                array(
                    'amount'        => (int)$helper->formatAmount($order->getBaseCustomerBalanceAmount()*-1),
                    'description'   => "Store Credit Amount"
                )
            );

            // Billing Details information
            $billingDetailsArray = array(
                'city'      => (string) $billingAddress->getCity(),
                'country'   => (string) $billingAddress->getCountryId(),
                'street'    => (string) $helper->getStreetDetails($billingAddress->getStreet(),0),
                'street2'   => (string) $helper->getStreetDetails($billingAddress->getStreet(),1),
                'zip'       => (string) $billingAddress->getPostcode(),
                'phone'     => (string) $billingAddress->getTelephone(),
            );

            if($billingDetailsArray['street2'] == '')
            {
                unset($billingDetailsArray['street2']);
            }

            if($billingAddress->getCountryId() === 'CA' || $billingAddress->getCountryId() === 'US')
            {
                $billingDetailsArray['state'] = (int) $billingAddress->getRegionCode();
            }else {
                $billingDetailsArray['state'] = (string) $billingAddress->getRegion();
            }

            if($billingAddress->getCustomerAddressId() === $shippingAddress->getCustomerAddressId())
            {
                $billingDetailsArray['useAsShippingAddress'] = true;
            } else {
                $billingDetailsArray['useAsShippingAddress'] = false;
            }

            // Shipping Details information
            $shippingDetailsArray = array(
                    'city'      => (string) $shippingAddress->getCity(),
                    'country'   => (string) $shippingAddress->getCountryId(),
                    'street'    => (string) $helper->getStreetDetails($shippingAddress->getStreet(),0),
                    'street2'   => (string) $helper->getStreetDetails($shippingAddress->getStreet(),1),
                    'zip'       => (string) $shippingAddress->getPostcode(),
                    'phone'     => (string) $shippingAddress->getTelephone(),
            );


            if($shippingDetailsArray['street2'] == '')
            {
                unset($shippingDetailsArray['street2']);
            }

            $shippingDetailsArray['state'] = (string) $shippingAddress->getRegionCode();

            if($shippingAddress->getCountryId() === 'CA' || $shippingAddress->getCountryId() === 'US')
            {
                $shippingDetailsArray['state'] = (int) $shippingAddress->getRegionCode();
            }else {
                $shippingDetailsArray['state'] = (string) $shippingAddress->getRegion();
            }

            // Shopping Cart Information
            foreach($orderItems as $item)
            {
                $itemArray = array(
                    'amount'        => (int) $helper->formatAmount($item->getBasePrice()),
                    'quantity'      => (int) $item->getQtyOrdered(),
                    'sku'           => (string) $item->getSku(),
                    'description'   => (string) substr($item->getName(),0,45)
                );

                $shoppingCartArray[] = $itemArray;
            }

            // Callback information
            $callbackArray = array();
            $callbackArray[] = array(
                'format'        => (string) 'json',
                'rel'           => (string) 'on_success',
                'retries'       => (int) Demac_Optimal_Model_Hosted_Client::CONNECTION_RETRIES,
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'synchronous'   => true,
                'uri'           => Mage::getBaseUrl()
            );

            // Callback information
            $redirectArray = array();
            $redirectArray[] = array(
                'rel'           => (string) 'on_success',
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'uri'           => Mage::getBaseUrl()
            );
            $redirectArray[] = array(
                'rel'           => (string) 'on_error',
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'uri'           => Mage::getBaseUrl()
            );
            $redirectArray[] = array(
                'rel'           => (string) 'on_decline',
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'uri'           => Mage::getBaseUrl()
            );
            $redirectArray[] = array(
                'rel'           => (string) 'on_timeout',
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'uri'           => Mage::getBaseUrl()
            );
            $redirectArray[] = array(
                'rel'           => (string) 'on_hold',
                'returnKeys'    => array(
                    'id',
                    'transaction.confirmationNumber',
                    'transaction.status'
                ),
                'uri'           => Mage::getBaseUrl()
            );

            // Add extra information to the order Data
            $data['shoppingCart']       = $shoppingCartArray;
            $data['ancillaryFees']      = $ancillaryFeesArray;
            $data['billingDetails']     = $billingDetailsArray;
            $data['shippingDetails']    = $shippingDetailsArray;
            $data['redirect']           = $redirectArray;
            $data['profile']            = $customerProfile;
            $data['extendedOptions']    = $extendedOptionsArray;
            $data['addendumData']       = $addendumDataArray;

            // Call Netbanks API and create the order
            $response = $client->createOrder($data);
            if (isset($response->link)) {
                foreach ($response->link as $link) {
                    if($link->rel === 'hosted_payment') {
                        $postURL = $link->uri;
                    }
                }
            } else {
                Mage::throwException($this->__("There was a problem creating the order"));
            }

            if(isset($postURL)){
                $paymentData = $this->_preparePayment($order->getPayment()->getData());

                $payment = $client->submitPayment($postURL,$paymentData);
                $orderStatus = $client->retrieveOrder($response->id);
                $transaction = $orderStatus->transaction;

                // Now we need to check the payment status if the transaction is available
                if(!isset($transaction) || $transaction->status == 'declined' || $transaction->status == 'cancelled')
                {
                    Mage::throwException($this->__("There was a processing your payment"));
                    return false;
                }else{
                    $order->addStatusHistoryComment(
                        'Netbanks Order Id: ' . $orderStatus->id .'<br/>' .
                        'Reference: # ' . $orderStatus->merchantRefNum .'<br/>' .
                        'Transaction Id: ' . $transaction->confirmationNumber .'<br/>' .
                        'Status: ' . $transaction->status .'<br/>'
                    );

                    $order->getPayment()->setAdditionalInformation('order', serialize(array('id' => $orderStatus->id)));
                    $order->getPayment()->setAdditionalInformation('transaction', serialize($transaction));
                    $order->getPayment()->setTransactionId($orderStatus->id);

                    return true;
                }
            }
        }
    }

    /**
     * Process the transaction response from
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        $order      = $observer->getOrder();
        $payment    = $order->getPayment();

        $isCustomerNotified = false; // Customer Notification true/false.


        if ($payment->getMethod() == 'optimal_hosted') {
            $orderAdditionalInformation = $payment->getAdditionalInformation();
            $transaction = unserialize($orderAdditionalInformation['transaction']);

            if(!empty($transaction->riskReasonCode))
            {
                $riskCode = Mage::getModel('optimal/risk')->loadByCode($transaction->riskReasonCode);
            }

            switch ($transaction->status) {
                case 'held':
                    $state   = Mage_Sales_Model_Order::STATE_HOLDED;
                    $status  = 'holded';
                    $comment = 'Order holded by ThreatMetrix.';

                    if ($riskCode->getStatus()) {
                        $status = $riskCode->getStatus();
                        $comment = 'ThreatMetrix Reason: ' . $transaction->description;
                    }
                    $order->setHoldBeforeState(Mage_Sales_Model_Order::STATE_PROCESSING);
                    $order->setHoldBeforeStatus('processing');

                    $order->setState($state, $status, $comment, $isCustomerNotified);
                    $order->save();
                    break;
                case 'pending':
                    $state   = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
                    $status  = 'payment_review';
                    $comment = 'Order payment pending.';

                    $order->setState($state, $status, $comment, $isCustomerNotified);
                    $order->save();
                    break;
                case 'abandoned':
                    $state   = Mage_Sales_Model_Order::STATE_CANCELED;
                    $status  = 'canceled';
                    $comment = 'Order was Abandoned.';

                    $order->setState($state, $status, $comment, $isCustomerNotified);
                    $order->save();
                    break;
            }
        }
    }


    /**
     * @param $paymentData
     * @return array
     */
    protected function _preparePayment($paymentData)
    {
        $fPaymentData = array(
            'cardNum'               => (string) $paymentData['cc_number'],
            'cardExpiryMonth'       => (int) $paymentData['cc_exp_month'],
            'cardExpiryYear'        => (int) $paymentData['cc_exp_year'],
            'cvdNumber'             => (string) $paymentData['cc_cid'],
        );

        return $fPaymentData;
    }
}