<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 1:29 PM
 */

class Op_Netbanx_Model_Method_Hosted extends Mage_Payment_Model_Method_Cc
{
    const METHOD_CODE = 'optimal_hosted';

    protected $_code                    = self::METHOD_CODE;
    protected $_canSaveCc               = false;
    protected $_canAuthorize            = true;
    protected $_canVoid                 = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_isGateway               = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;

    protected $_formBlockType   = 'optimal/form_creditcard';
    protected $_infoBlockType   = 'optimal/info_creditcard';

    public function isGateway()
    {
        return $this->_isGateway;
    }

    public function canRefund()
    {
        return $this->_canRefund;
    }

    public function canVoid(Varien_Object $payment)
    {
        return $this->_canVoid;
    }

    public function canCapturePartial()
    {
        return $this->_canCapturePartial;
    }

    public function canAuthorize()
    {
        return $this->_canAuthorize;
    }

    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $transactionId
     * @param $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @param bool $message
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
                                       array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        /**
         * Its for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();

        $profileId = $data->getProfileId();

        $info->setOptimalUseInterac($data->getOptimalUseInterac());

        if(isset($profileId) && ($profileId != 0)) {
            $profile = Mage::getModel('optimal/creditcard')
                ->load($profileId, 'entity_id');
            $expiry = explode('/', $profile->getCardExpiration());
            $expiry[1] = 2000 + $expiry[1];
            $info
                ->unsOptimalCreateProfile()
                ->setCcType($profile->getCardNickname())
                ->setCcOwner($profile->getCardHolder())
                ->setCcLast4($profile->getLastFourDigits())
                ->setCcExpMonth($expiry[0])
                ->setCcExpYear($expiry[1])
                ->setCcCidEnc($info->encrypt($data->getCcCid()))
                ->setOptimalProfileId($profileId);
            $info->save();
        } else {
            $info
                ->setCcType($data->getCcType())
                ->setCcOwner($data->getCcOwner())
                ->setCcLast4(substr($data->getCcNumber(), -4))
                ->setCcNumber($data->getCcNumber())
                ->setCcCid($data->getCcCid())
                ->setCcExpMonth($data->getCcExpMonth())
                ->setCcExpYear($data->getCcExpYear())
                ->setCcSsIssue($data->getCcSsIssue())
                ->setCcSsStartMonth($data->getCcSsStartMonth())
                ->setCcSsStartYear($data->getCcSsStartYear())
                ->setOptimalCreateProfile($data->getOptimalCreateProfile());
        }

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        $skip3d = Mage::getStoreConfig('payment/optimal_hosted/skip3D', Mage::app()->getStore()->getStoreId());
        $allowInterac = Mage::getStoreConfig('payment/optimal_hosted/allow_interac', Mage::app()->getStore()->getStoreId());
        $info = $this->getInfoInstance();
        $last4 = $info->getData('cc_last4');

        if ((!$skip3d || $allowInterac) && empty($last4)) { // Do not require Credit Card info if NOT skipping 3D verification or when payment method is Interac
            return $this;
        }

        $errorMsg = false;
        $availableTypes = explode(',',$this->getConfigData('cctypes'));


        $optimalProfileId = $info->getOptimalProfileId();
        if ($optimalProfileId) {

            //validate credit card verification number
            if ($errorMsg === false && $this->hasVerification()) {
                $ccId = $info->getCcCid();
                if (!isset($ccId)){
                    $errorMsg = Mage::helper('payment')->__('Please enter a valid credit card verification number.');
                }
            }

            if($errorMsg){
                Mage::throwException($errorMsg);
            }

        } else {
            parent::validate();
        }

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
        }

        try {
            $error = false;
            $customerSession = Mage::getSingleton('customer/session');
            $adminQuoteSession = Mage::getSingleton('adminhtml/session_quote');
            if ($customerSession->isLoggedIn()){
                $customerId = $customerSession->getId();
                $customer = Mage::getModel('customer/customer')->load($customerId);
            } elseif($adminQuoteSession->getCustomerId()){
                $customer = Mage::getModel('customer/customer')->load($adminQuoteSession->getCustomerId());
            }

            if ( $amount < 0 ) {
                $error = Mage::helper('paygate')->__('Invalid amount for capture.');
            }

            if ( $error !== false ) {
                Mage::throwException('There was a problem authorizing the order.');
            }

            $order      = $payment->getOrder();
            $quote      = Mage::getModel('sales/quote')->load($order->getQuoteId());
            $client     = Mage::getModel('optimal/hosted_client');
            $helper     = Mage::helper('optimal');

            $createProfile = false;

            $orderData      = array();
            $customerData   = array();
            // Get order data
            $orderData['remote_ip']         = $order->getRemoteIp();
            $orderData['order_items']       = $order->getAllVisibleItems();
            $orderData['increment_id']      = $order->getIncrementId();
            $orderData['customer_email']    = $order->getCustomerEmail();
            $orderData['billing_address']   = $order->getBillingAddress();
            $orderData['shipping_address']  = $order->getShippingAddress();

            $orderData['base_tax_amount']               = $order->getBaseTaxAmount();
            $orderData['gift_cards_amount']             = $quote->getBaseGiftCardsAmountUsed();
            $orderData['base_grand_total']              = $order->getBaseGrandTotal();
            $orderData['base_currency_code']            = $order->getBaseCurrencyCode();
            $orderData['base_shipping_amount']          = $order->getBaseShippingAmount();
            $orderData['base_discount_amount']          = $order->getBaseDiscountAmount();
            $orderData['base_customer_balance_amount']  = $order->getBaseCustomerBalanceAmount();





            // 20150806 - CollinsHarper update for issue with 3rd party fees / rewards points.
            // if the totals do not match. we add the different to the discount.

            $pointsData = Mage::getSingleton('checkout/session')->getData('reward_sales_rules');
            if($pointsData && isset($pointsData['base_discount'])) {
                $orderData['gift_cards_amount'] += $pointsData['base_discount'];
            }

            $paymentData = $payment->getData();

            $skip3d         = Mage::getStoreConfig('payment/optimal_hosted/skip3D', Mage::app()->getStore()->getStoreId());
            $allowInterac   = Mage::getStoreConfig('payment/optimal_hosted/allow_interac', Mage::app()->getStore()->getStoreId());

            if (isset($paymentData['optimal_create_profile'])) {
                $createProfile = $paymentData['optimal_create_profile'];
            }

            $useInterac = false;
            if ($this->_useInterac($quote)) {
                $orderData['use_interac'] = 1;
                $createProfile = false;
                $useInterac = true;
            }

            $checkoutMethod = Mage::getSingleton('checkout/type_onepage')->getCheckoutMethod();

            if ($checkoutMethod != 'guest') {
                $quote = Mage::getSingleton('sales/quote')->load($order->getQuoteId());
                $billing = $quote->getBillingAddress();
                $customerData['is_guest'] = false;
                $customerData['lastname'] = (string)$billing->getLastname();
                $customerData['firstname'] = (string)$billing->getFirstname();
                $customerData['email'] = (string)$billing->getEmail();

            } elseif ($customerSession->isLoggedIn()){ // Get customer information
                $customerData = $customer->getData();
                $customerData['is_guest'] = false;
                $customerData['lastname'] = (string)$customer->getLastname();
                $customerData['firstname'] = (string)$customer->getFirstname();
                $customerData['email'] = (string)$customer->getEmail();

            } else {
                if ($createProfile) {
                    $customerData['is_guest'] = false;
                    $customerData['lastname'] = (string)$order->getCustomerLastname();
                    $customerData['firstname'] = (string)$order->getCustomerFirstname();
                    $customerData['email'] = (string)$order->getCustomerEmail();
                } else {
                    $customerData['is_guest'] = true;
                    $customerData['lastname'] = (string)$order->getCustomerLastname();
                    $customerData['firstname'] = (string)$order->getCustomerFirstname();
                    $customerData['email'] = (string)$order->getCustomerEmail();
                }
            }

            $savedCreditCardProfileId = 0;
            if(isset($paymentData['optimal_profile_id']) && $paymentData['optimal_profile_id'] > 0) {
                $savedCreditCardProfileId = $customerData['profile_id'] = $paymentData['optimal_profile_id'];
            }

            // Call the helper and get the data for netbank
            $data = $helper->prepareNetbanksOrderData($orderData ,$customerData, $createProfile);

            // Call Netbanks API and create the order
            $response = $client->createOrder($data);
            if (isset($response->link)) {
                foreach ($response->link as $link) {
                    if($link->rel === 'hosted_payment') {
                        $postURL = $link->uri;
                    }
                }
            } else {
                Mage::throwException(Mage::helper('optimal')->__("There was a problem creating the order"));
            }

            // Redirect the Customer if 3D-Secure verification is turned on
            if (isset($postURL) && (!$skip3d || ($allowInterac && $useInterac))) {

                try {
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                    $order->setStatus('pending_payment');
                    $order->addStatusHistoryComment('Redirecting the Customer to Optimal Payments for Payment Authorisation', 'pending_payment');
                    $order->addStatusHistoryComment(
                        'Netbanks Order Id: ' . $response->id .'<br/>' .
                        'Reference: # ' . $response->merchantRefNum .'<br/>'
                    );
                    $order->setIsNotified(false);
                    $order->save();
                    $checkoutSess = Mage::getSingleton('checkout/session');
                    $checkoutSess->unsQuoteId();
                    $checkoutSess->unsRedirectUrl();

                    $payment->setStatus('PENDING');
                    $payment->setAdditionalInformation('order', serialize(array('id' => $response->id)));
                    $payment->setTransactionId($response->id);
                    // magento will automatically close the transaction on auth preventing the invoice from being captured online.
                    $payment->setIsTransactionClosed(false);
                    $payment->save();

                    $this->orderRedirectUrl($postURL);

                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'demac_optimal.log');
                    Mage::logException($e);
                    $checkoutSess->addError(Mage::helper('optimal')->__('An error was encountered while redirecting to the payment gateway, please try again later.'));
                    $this->_handlePaymentFailure();
                    $this->orderRedirectUrl(Mage::getBaseUrl() . 'checkout/cart');
                }

                return $this;
            }

            if(isset($postURL)) {
                $paymentData = $this->_preparePayment($payment->getData());

                if(isset($customerData['profile_id']))
                {
                    unset($paymentData['cardNum']);
                    unset($paymentData['cardExpiryMonth']);
                    unset($paymentData['cardExpiryYear']);
                    $paymentData['id'] = $customerData['profile_id'];
                    $paymentData['paymentToken'] = $data['profile']['paymentToken'];
                }

                $paymentResponse    = $client->submitPayment($postURL,$paymentData);
                $orderStatus        = $this->_getOptimalOrderStatus($client, $response->id);

                if (!isset($orderStatus->transaction))
                {
                    Mage::log('Aborting ... Transaction Object not present in orderStatus', null, 'demac_optimal.log');
                    Mage::throwException('Something went wrong with your transaction. Please contact support.');
                }

                $transaction        = $orderStatus->transaction;

                // Now we need to check the payment status if the transaction is available
                if($transaction->status == 'declined' || $transaction->status == 'cancelled')
                {
                    Mage::throwException(Mage::helper('optimal')->__("There was an error processing your payment"));
                }

                // Check the order status for the profile information and try to save it
                if($createProfile){
                    if(isset($orderStatus->profile)){
                        $profile = Mage::getModel('optimal/creditcard');
                        if (isset($orderStatus->profile->merchantCustomerId)) {
                            $merchantCustomerId = $orderStatus->profile->merchantCustomerId;
                        }

                        if(!isset($merchantCustomerId))
                        {
                            $merchantCustomerId = Mage::helper('optimal')->getMerchantCustomerId($order->getCustomerId());
                            $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];
                        }

                        // Set Profile Info
                        $profile->setCustomerId($order->getCustomerId());
                        $profile->setProfileId($orderStatus->profile->id);
                        $profile->setMerchantCustomerId($merchantCustomerId);
                        $profile->setPaymentToken($orderStatus->profile->paymentToken);

                        // Set Nickname
                        $cardName = $orderStatus->transaction->card->brand;
                        $profile->setCardNickname(Mage::helper('optimal')->processCardNickname($cardName));

                        // Set Nickname
                        //$cardHolder = $payment->getCcOwner();
                        $cardHolder = $customerData['firstname'] . ' ' . $customerData['lastname']; // $params['firstname'] . $params['lastname'];
                        $profile->setCardHolder($cardHolder);

                        // Set Card Info
                        $lastfour = $payment->getCcLast4();
                        $profile->setLastFourDigits($lastfour);

                        // Format card expiration date [todo]: Make a helper function
                        $expirationDate = sprintf("%02s", $paymentData['cardExpiryMonth']) . "/"  . substr($paymentData['cardExpiryYear'], -2);

                        $profile->setCardExpiration($expirationDate);

                        $profile->save();
                    }else {
                        Mage::throwException(Mage::helper('optimal')->__("There was a problem saving your payment information."));
                    }
                }



                $order->addStatusHistoryComment(
                    'Netbanks Order Id: ' . $orderStatus->id .'<br/>' .
                    'Reference: # ' . $orderStatus->merchantRefNum .'<br/>' .
                    'Transaction Id: ' . $transaction->confirmationNumber .'<br/>' .
                    'Status: ' . $transaction->status .'<br/>'
                );

                $payment->setStatus('APPROVED');
                $payment->setAdditionalInformation('order', serialize(array('id' => $orderStatus->id, 'optimal_profile_id' => $savedCreditCardProfileId)));
                $payment->setAdditionalInformation('transaction', serialize($transaction));
                $payment->setTransactionId($orderStatus->id);
                // magento will automatically close the transaction on auth preventing the invoice from being captured online.
                $payment->setIsTransactionClosed(false);
                $payment->setAdditionalInformation('payment_type', $this->getInfoInstance()->getCcType());
            }

            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException("Optimal Gateway Transaction Error: " . $e->getMessage());
            Mage::helper('optimal')->cleanMerchantCustomerId(Mage::getSingleton('customer/session')->getId());
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

        if($paymentData['optimal_create_profile'])
        {
            $fPaymentData['storeCardIndicator'] = (bool) $paymentData['optimal_create_profile'];
        }

        return $fPaymentData;
    }

    public function getOptimalOrderStatus($client, $id, $count = 0) {
        return $this->_getOptimalOrderStatus($client, $id, $count);
    }

    protected function _getOptimalOrderStatus($client, $id, $counter = 0)
    {
        if($counter >= 3){
            Mage::throwException('There was a problem retrieving the order information. Please contact support.');
        }

        Mage::log('Get-Optimal-Order-Status Try #: ' . ($counter + 1), null, 'demac_optimal.log');

        try{
            return $client->retrieveOrder($id);
        } catch (Op_Netbanx_Model_Hosted_Exception $e) { // in case when Error is generated from Optimal
            Mage::throwException($e->getMessage());
        } catch(Exception $e) {
            $counter++;
            $this->_getOptimalOrderStatus($client, $id, $counter);
        }

    }

    protected function _useInterac($quote) {
        $quotePayment = $quote->getPayment();
        $useInterac = $quotePayment->getOptimalUseInterac();

        $allowInterac = Mage::getStoreConfig('payment/optimal_hosted/allow_interac', Mage::app()->getStore()->getStoreId());

        return $allowInterac && $useInterac;
    }


    /**
     * Send capture request to gateway
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Mage_Authorizenet_Model_Directpost
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        // Start Refactor : Fix capture for multistore setup
        $helper = Mage::helper('optimal');
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payment')->__('Invalid amount for capture.'));
        }

        try {

            $transactionMode = Mage::getStoreConfig('payment/optimal_hosted/payment_action');
            if($transactionMode == Op_Netbanx_Model_Method_Hosted::ACTION_AUTHORIZE_CAPTURE)
            {
                $result = $this->authorize($payment, $amount);
                return $result;
            }

            $additionalInformation = $payment->getAdditionalInformation();

            if (isset($additionalInformation['transaction'])) {

                $paymentData = unserialize($additionalInformation['transaction']);
                $orderData = unserialize($additionalInformation['order']);

                $order = $payment->getOrder();
                $payment->setAmount($amount);

                $client = Mage::getModel('optimal/hosted_client', array('store_id' => $order->getStoreId()));

                $transactionStatus = $client->retrieveOrder($orderData['id']);

                if ($transactionStatus->transaction->status == 'held')
                {
                    // Prepare api order update
                    $transactionData = array(
                        'transaction' => array(
                            'status' => 'success'
                        )
                    );
                    $response = $client->updateOrder($transactionData, $orderData['id']);
                }

                $data = array(
                    'amount' => (int)$helper->formatAmount($amount),
                    'merchantRefNum' => (string)$paymentData->merchantRefNum
                );

                $response = $client->settleOrder($data, $orderData['id']);
                $orderStatus = $client->retrieveOrder($orderData['id']);
                $transaction = $orderStatus->transaction;

                $associatedTransactions = $transaction->associatedTransactions;

                $payment->setAdditionalInformation('transaction', serialize($transaction));

                $order->addStatusHistoryComment(
                    'Trans Type: ' . $response->authType . '<br/>' .
                    'Confirmation Number: ' . $response->confirmationNumber . '<br/>' .
                    'Transaction Amount: ' . $response->amount / 100 . '<br/>'
                );

                return $this;
            } else {
                Mage::throwException('Transaction information is not properly set. Please contact support@demacmedia.com');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException("Optimal Gateway Transaction Error: " . $e->getMessage());
        }
    }


    /**
     * Void the payment through gateway
     *
     * @param Varien_Object $payment
     * @return Mage_Authorizenet_Model_Directpost
     * @throws Mage_Core_Exception
     */
    public function void(Varien_Object $payment)
    {
        try {
            $additionalInformation = $payment->getAdditionalInformation();

            if (isset($additionalInformation['transaction'])) {
                $order = $payment->getOrder();

                $client = Mage::getModel('optimal/hosted_client', array('store_id' => $order->getStoreId()));

                $paymentData    = unserialize($additionalInformation['transaction']);
                $orderData      = unserialize($additionalInformation['order']);

                $transactionStatus = $client->retrieveOrder($orderData['id']);

                if ($transactionStatus->transaction->status == 'held')
                {
                    // Prepare api order update
                    $data = array(
                        'transaction' => array(
                            'status' => 'cancelled'
                        )
                    );

                    $response = $client->updateOrder($data, $orderData['id']);

                } elseif($transactionStatus->transaction->status == 'success') {
                    $response = $client->cancelOrder($orderData['id']);
                } else {
                    Mage::throwException('Unable to void transaction. Please contact support@demacmedia.com');
                }

                $payment
                    ->setIsTransactionClosed(1)
                    ->setShouldCloseParentTransaction(1);


                $order->addStatusHistoryComment(
                    'Transaction Voided <br/>' .
                    'Trans Type: ' . $response->authType .'<br/>'.
                    'Confirmation Number: ' . $response->confirmationNumber .'<br/>'.
                    'Transaction Amount: ' . $response->amount/100 .'<br/>'
                );

                return $this;


            } else {
                Mage::throwException('Transaction information is not properly set. Please contact support@demacmedia.com');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException("Optimal Gateway Transaction Error: " . $e->getMessage());
        }
    }

    /**
     * Refund the amount
     * Need to decode Last 4 digits for request.
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Mage_Authorizenet_Model_Directpost
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $helper = Mage::helper('optimal');

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for refund.'));
        }

        if (!$payment->getParentTransactionId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        try {
            $additionalInformation = $payment->getAdditionalInformation();

            if (isset($additionalInformation['transaction'])) {
                $order = $payment->getOrder();

                $client = Mage::getModel('optimal/hosted_client', array('store_id' => $order->getStoreId()));

                $paymentData    = unserialize($additionalInformation['transaction']);
                $orderData      = unserialize($additionalInformation['order']);

                $data = array(
                    'amount'            => (int)$helper->formatAmount($amount),
                    'merchantRefNum'    => (string)$paymentData->merchantRefNum
                );

                if(is_null($paymentData->associatedTransactions[0]->reference))
                {
                    $transactionId = $payment->getLastTransId();
                }else {
                    $transactionId = $paymentData->associatedTransactions[0]->reference;

                }

                $response = $client->refundOrder($data,$transactionId);
                $order->addStatusHistoryComment(
                    'Trans Type: ' . $response->authType .'<br/>',
                    'Confirmation Number: ' . $response->confirmationNumber .'<br/>',
                    'Transaction Amount: ' . $response->amount/100 .'<br/>'
                );
                return $this;

            } else {
                Mage::throwException('Transaction information is not properly set. Please contact support@demacmedia.com');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException("Optimal Gateway Transaction Error: " . $e->getMessage());
        }

        return $this;
    }

    public function orderRedirectUrl($url = null)
    {
        static $gatewayUrl;

        if (!$url) {
            return $gatewayUrl;
        }

        $gatewayUrl = $url;

        return $gatewayUrl;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->orderRedirectUrl();
    }

    /**
     * Handle Payment Failure
     * - Cancel the order
     * - Restore the quote
     *
     * Cancel Order and attempt to restore cart.
     *
     */
    protected function _handlePaymentFailure()
    {
        $session = Mage::getSingleton('checkout/session');

        if ($session->getLastRealOrderId()) {
            try {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $order->cancel()->save();
                    $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                    if ($quote->getId()) {
                        $quote->setIsActive(1)
                            ->setReservedOrderId(null)
                            ->save();

                        $session->replaceQuote($quote)
                            ->unsLastRealOrderId();
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}
