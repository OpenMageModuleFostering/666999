<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 2:05 PM
 */

class Demac_Optimal_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param $amount
     * @return int
     */
    public function formatAmount($amount)
    {
        $amount = (int)(string)((number_format($amount,2,'.','')) * 100);
        return $amount;
    }

    /**
     * @param $address
     * @param $position
     * @return string
     */
    public function getStreetDetails($address, $position)
    {
        if(isset($address[$position]))
        {
            return $address[$position];
        } else {
            return '';
        }
    }

    public function getMerchantCustomerId($customer_id = false) {

        if($customer_id !== false) {
            $customer = Mage::getModel('optimal/merchant_customer')->load($customer_id, 'customer_id');
            if($customer->getMerchantCustomerId()) {
                return $this->processMerchantCustomerId($customer);
            }
        }

        $customer = Mage::getModel('optimal/merchant_customer');
        if($customer_id) {
            $customer->setCustomerId($customer_id);
        }
        $customer->setDataChanges(true); // force save in case we need to save just the ID.
        $customer->save();

        $cData = Mage::getModel('optimal/merchant_customer')->getCollection()
            ->addFieldToFilter('customer_id', $customer_id)
            ->getFirstItem();

        return $this->processMerchantCustomerId($customer);
    }

    protected function processMerchantCustomerId($merchantCustomer) {
        $internalId = $merchantCustomer->getMerchantCustomerId();
        $customerId = $merchantCustomer->getCustomerId();
        $currentStoreId = Mage::app()->getStore()->getStoreId();
        $apiLoginId = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/optimal_hosted/login'), $currentStoreId);
        return array(
            'internal_id' => $internalId,
            'merchant_customer_id' => md5($apiLoginId . $internalId . $customerId)
        );
    }

    public function cleanMerchantCustomerId($customer_id = false) {
        if($customer_id) {
            $cards = Mage::getModel('optimal/creditcard')
                ->getCollection();
            $cards->addFieldToFilter('customer_id', array('eq', $customer_id));
            if($cards->count() == 0) {
                Mage::getModel('optimal/merchant_customer')->load($customer_id, 'customer_id')->delete();
            }
        }
    }

    function processCardNickname($nickname) {
        return str_replace('Amex', 'American Express', str_replace('Mastercard', 'MasterCard', ucwords($nickname)));
    }

    /**
     * Prepare data for netbanks order creation
     *
     * @param $orderData
     * @param $customerData
     * @param bool $saveCard
     * @param null $transactionMode
     * @return array
     */
    public function prepareNetbanksOrderData($orderData, $customerData, $saveCard = false, $transactionMode = null)
    {
        $shoppingCartArray  = array();
        $orderItems         = $orderData['order_items'];
        $billingAddress     = $orderData['billing_address'];
        $shippingAddress    = $orderData['shipping_address'];

        // Order extended options
        $extendedOptionsArray = array();

        // Minimum order information needed
        $data = array(
            'totalAmount'               => (int) $this->formatAmount($orderData['base_grand_total']),
            'currencyCode'              => (string) $orderData['base_currency_code'],
            'merchantRefNum'            => (string) $orderData['increment_id'] . time(),
        );

        if(strlen(Mage::getStoreConfig('payment/optimal_hosted/merchant_email')) > 0) {
            $data['merchantNotificationEmail'] = Mage::getStoreConfig('payment/optimal_hosted/merchant_email');
        }

        $data['customerNotificationEmail'] = (string) $orderData['customer_email'];
        if(Mage::getStoreConfig('payment/optimal_hosted/email_customer') != 1) {
            $extendedOptionsArray[] = array(
                'key'       => (string) 'suppressCustomerEmail',
                'value'     => true
            );
        }

        // Customer Profile information


        // 1) The customer wants to pay with a saved profile
        //    - We check for a profile id and a payment token
        // 2) The customer wants to add a new card to his profile
        //    - We check for the customer profile id

        $customerProfile['lastName']    = (string) $customerData['lastname'];
        $customerProfile['firstName']   = (string) $customerData['firstname'];
        $merchantCustomerId             = null;

        $skip3d = Mage::getStoreConfig('payment/optimal_hosted/skip3D', Mage::app()->getStore()->getStoreId());
        $profilesEnabled = Mage::getStoreConfig('payment/optimal_profiles/active', Mage::app()->getStore()->getStoreId());

        if (!$customerData['is_guest']) {

            if (!isset($customerId)) {
                $customerId = Mage::getSingleton('customer/session')->getId();
            }

            $merchantCustomerId = $this->getMerchantCustomerId($customerId);
            $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];
            $profile = Mage::getModel('optimal/creditcard')->loadByMerchantCustomerId($merchantCustomerId);

            // If not skipping 3D and CreateProfiles is TRUE
            if (!$skip3d && $profilesEnabled) {

                if ($profile->getProfileId()) {

                    $customerProfile['id'] = (string) $profile->getProfileId();

                } elseif (empty($customerProfile['merchantCustomerId'])) {
                    $merchantCustomerId = $this->getMerchantCustomerId($customerId);
                    $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];
                    $customerProfile['merchantCustomerId']  = $merchantCustomerId;
                }

            } elseif (!$skip3d && !$profilesEnabled) {

                if ($profile->getProfileId()) {
                    $customerProfile['id']    = $profile->getProfileId();
                }

            } else {
                if (!empty($customerData['profile_id'])) { // Check if there is a profile_id being passed
                    $profile = Mage::getModel('optimal/creditcard')->load((int)$customerData['profile_id']);
                    if ($profile->getProfileId()) {
                        $customerProfile['id'] = (string)$profile->getProfileId();

                        // In case the profile exists
                        if (!$saveCard) {
                            $customerProfile['paymentToken'] = (string)$profile->getPaymentToken();
                        }
                    } else {
                        Mage::throwException($this->__("The select profile does not exists."));
                    }
                } elseif($saveCard) {

                    // Check for existing profile id
                    $customerId = Mage::getSingleton('customer/session')->getId();
                    $merchantCustomerId = $this->getMerchantCustomerId($customerId);
                    $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];
                    $profile = Mage::getModel('optimal/creditcard')->loadByMerchantCustomerId($merchantCustomerId);

                    if($profile->getProfileId()) {
                        $customerProfile['id'] = (string)$profile->getProfileId();
                    }else {
                        $customerProfile['merchantCustomerId']    = $merchantCustomerId;
                    }
                }
            }
        }

        // Need to be sure this matches the store on which the order was placed
        if (is_null($transactionMode)) {
            $transactionMode = Mage::getStoreConfig('payment/optimal_hosted/payment_action');
        }

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

        $skip3d = Mage::getStoreConfig('payment/optimal_hosted/skip3D', Mage::app()->getStore()->getStoreId());

        if($skip3d)
        {
            $extendedOptionsArray[] = array(
                'key'       => (string) 'skip3D',
                'value'     => true
            );
            // since we are skipping 3D-Secure check, hence we can process the order via Silent Post
            $extendedOptionsArray[] = array(
                'key'       => 'silentPost',
                'value'     => true
            );
        } else {
            $extendedOptionsArray[] = array(
                'key'       => (string) 'skip3D',
                'value'     => false
            );

            $extendedOptionsArray[] = array(
                'key'       => 'disablePaymentPageEditing',
                'value'     => true
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
                'amount'        => (int)$this->formatAmount($orderData['base_shipping_amount']),
                'description'   => "Shipping Amount"
            ),
            array(
                'amount'        => (int)$this->formatAmount($orderData['base_tax_amount']),
                'description'   => "Tax Amount"
            ),
            array(
                'amount'        => (int)$this->formatAmount($orderData['base_discount_amount']),
                'description'   => "Discount Amount"
            ),
            array(
                'amount'        => (int)$this->formatAmount($orderData['base_customer_balance_amount']*-1),
                'description'   => "Store Credit Amount"
            )
        );

        if (!empty($orderData['gift_cards_amount'])) {
            $ancillaryFeesArray[] = array(
                'amount'        => (-100 * $orderData['gift_cards_amount']),
                'description'   => "Gift Cards Amount"
            );
        }

        // Billing Details information
        $billingDetailsArray = array(
            'city'      => (string) $billingAddress->getCity(),
            'country'   => (string) $billingAddress->getCountryId(),
            'street'    => (string) $this->getStreetDetails($billingAddress->getStreet(),0),
            'street2'   => (string) $this->getStreetDetails($billingAddress->getStreet(),1),
            'zip'       => (string) $billingAddress->getPostcode(),
            'phone'     => (string) $billingAddress->getTelephone(),
        );

        if($billingDetailsArray['street2'] == '')
        {
            unset($billingDetailsArray['street2']);
        }

        if($billingAddress->getCountryId() === 'CA' || $billingAddress->getCountryId() === 'US')
        {
            $billingDetailsArray['state'] = (string) $billingAddress->getRegionCode();
        } else {
            if((string) $billingAddress->getRegion() != '') {
                $billingDetailsArray['state'] = (string) $billingAddress->getRegion();
            }
        }

        // Start Refactor : Work around for downloadable and virtual products
        if (!$shippingAddress) {
            $shippingAddress = $billingAddress;
        }
        // End Refactor

        if(!$customerData['is_guest'] && $billingAddress->getCustomerAddressId() === $shippingAddress->getCustomerAddressId())
        {
            $billingDetailsArray['useAsShippingAddress'] = true;
        } else {
            $billingDetailsArray['useAsShippingAddress'] = false;
        }

        // Shipping Details information
        $shippingDetailsArray = array(
            'city'      => (string) $shippingAddress->getCity(),
            'country'   => (string) $shippingAddress->getCountryId(),
            'street'    => (string) $this->getStreetDetails($shippingAddress->getStreet(),0),
            'street2'   => (string) $this->getStreetDetails($shippingAddress->getStreet(),1),
            'zip'       => (string) $shippingAddress->getPostcode(),
            'phone'     => (string) $shippingAddress->getTelephone(),
        );


        if($shippingDetailsArray['street2'] == '')
        {
            unset($shippingDetailsArray['street2']);
        }

        if($shippingAddress->getCountryId() === 'CA' || $shippingAddress->getCountryId() === 'US')
        {
            $shippingDetailsArray['state'] = (string) $shippingAddress->getRegionCode();
        } else {
            if((string) $shippingAddress->getRegion() != '') {
                $shippingDetailsArray['state'] = (string) $shippingAddress->getRegion();
            }
        }

        // Shopping Cart Information
        foreach($orderItems as $item)
        {
            $itemAmountTotal += (float) $item->getBasePrice();
            $itemArray = array(
                'amount'        => (int) $this->formatAmount($item->getBasePrice()),
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
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
        );

        // Callback information
        $redirectArray = array();
        $returnKeys = array('id', 'transaction.confirmationNumber', 'transaction.status', 'profile.id', 'profile.paymentToken');

        $redirectArray[] = array(
            'rel'           => (string) 'on_success',
            'returnKeys'    => $returnKeys,
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
        );
        $redirectArray[] = array(
            'rel'           => (string) 'on_error',
            'returnKeys'    => $returnKeys,
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
        );
        $redirectArray[] = array(
            'rel'           => (string) 'on_decline',
            'returnKeys'    => $returnKeys,
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
        );
        $redirectArray[] = array(
            'rel'           => (string) 'on_timeout',
            'returnKeys'    => $returnKeys,
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
        );
        $redirectArray[] = array(
            'rel'           => (string) 'on_hold',
            'returnKeys'    => $returnKeys,
            'uri'           => Mage::getBaseUrl() . 'optimal/handler/callback'
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

        Mage::log($data, null, 'NetBanxData.log');
        return $data;
    }

    public function getMsgByCode($code = null)
    {
        $model = Mage::getModel('optimal/errorcode')->loadByCode($code);
        if (!$model->getCode()) {
            return null;
        }

        $msg = $model->getMessage();
        $customMsg = $model->getCustomMessage();

        if ($model->getActive() == 1) {
            return $customMsg ? $customMsg : $msg;
        }

        return $msg;
    }

    public function restoreQuote()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $order = $checkoutSession->getLastRealOrder();
        if ($order->getId()) {
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $checkoutSession->replaceQuote($quote)
                    ->unsLastRealOrderId();
                return true;
            }
        }
        return false;
    }

    public function canShow()
    {
        $skip3d = Mage::getStoreConfig('payment/optimal_hosted/skip3D', Mage::app()->getStore()->getStoreId());
        $profilesEnabled = Mage::getStoreConfig('payment/optimal_profiles/active', Mage::app()->getStore()->getStoreId());
        $show = ($profilesEnabled && $skip3d);

        return $show;
    }

}
