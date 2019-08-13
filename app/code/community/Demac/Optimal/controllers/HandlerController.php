<?php

class Demac_Optimal_HandlerController extends Mage_Core_Controller_Front_Action
{
    /**
     * Callback Action
     *
     * Handle Success/Failure response from Payment Gateway
     */
    public function callbackAction()
    {
        $params             = $this->getRequest()->getParams();
        $session            = Mage::getSingleton('checkout/session');

        $status             = $params['transaction_status'];
        $confirmation       = $params['transaction_confirmationNumber'];
        $optimalOrderId     = $params['id'];

        if ($status != 'success') {
            $session->addError($this->__('Payment failed, please review your payment information and try again.'));
            $this->_handlePaymentFailure();
            $this->_redirect('checkout/cart');
            return;
        }

        try {
            $this->_handlePaymentSuccess($session, $params);
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $this->_handlePaymentFailure();
            $this->_redirect('checkout/onepage/failure');
        }

    }

    /**
     * Handle Payment Failure
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
                }
                Mage::helper('optimal')->restoreQuote();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

    }

    /**
     * Handle Payment Success
     *
     * Update Order status and create invoice
     *
     * @param $session
     * @param $params
     */
    protected function _handlePaymentSuccess($session, $params)
    {
        $status             = $params['transaction_status'];
        $confirmation       = $params['transaction_confirmationNumber'];
        $optimalOrderId     = $params['id'];

        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        $payment = $order->getPayment();

        // Now let's get the Order's status from Optimal
        $client             = Mage::getModel('optimal/hosted_client');
        $orderStatus        = Mage::getModel('optimal/method_hosted')->getOptimalOrderStatus($client, $optimalOrderId);
        $transaction        = $orderStatus->transaction;

        $customerSession = Mage::getSingleton('customer/session');
        list($month, $year) = explode('/', $transaction->card->expiry);

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getId();
            $customerData = Mage::getModel('customer/customer')->load($customerId)->getData();
            $Card = Mage::getModel('optimal/creditcard');
            $digits = $transaction->card->lastDigits;

            $expiration = $month . '/' . substr($year, -2);
            $profile = $Card->getCollection()
                            ->addFieldToFilter('customer_id', $customerId)
                            ->addFieldToFilter('last_four_digits', $digits)
                            ->addFieldToFilter('card_expiration', $expiration)
                            ->getFirstItem();

            $merchantCustomerId = Mage::helper('optimal')->getMerchantCustomerId($customerId);
            $merchantCustomerId = $merchantCustomerId['merchant_customer_id'];

            // this means the CC is not saved
            if (empty($profile)) {
                $profile = Mage::getModel('optimal/creditcard');
            }

            // Set Profile Info
            $profile->setCustomerId($customerId);
            $profile->setProfileId($orderStatus->profile->id);
            $profile->setMerchantCustomerId($merchantCustomerId);
            $profile->setPaymentToken($orderStatus->profile->paymentToken);

            // Set Nickname
            $cardName = $orderStatus->transaction->card->brand;
            $profile->setCardNickname(Mage::helper('optimal')->processCardNickname($cardName));

            // Set Nickname
            $cardHolder = $customerData['firstname'] . ' ' . $customerData['lastname']; // $params['firstname'] . $params['lastname'];
            $profile->setCardHolder($cardHolder);

            // Set Card Info
            $profile->setLastFourDigits($transaction->card->lastDigits);

            $profile->setCardExpiration($expiration);

            $profile->save();
        }

        if (!isset($cardHolder)) {
            $cardHolder = $orderStatus->profile->firstName . ' ' . $orderStatus->profile->lastName;
        }

        $order->addStatusHistoryComment(
            'Netbanks Order Id: ' . $orderStatus->id .'<br/>' .
            'Reference: # ' . $orderStatus->merchantRefNum .'<br/>' .
            'Transaction Id: ' . $transaction->confirmationNumber .'<br/>' .
            'Status: ' . $transaction->status .'<br/>'
        );

        $payment->setStatus('APPROVED');
        $payment->setAdditionalInformation('order', serialize(array('id' => $optimalOrderId)));
        $payment->setAdditionalInformation('transaction', serialize($transaction));
        $payment->setTransactionId($optimalOrderId);
        // magento will automatically close the transaction on auth preventing the invoice from being captured online.
        $payment->setIsTransactionClosed(false);
        $payment->setCcOwner($cardHolder)
            ->setCcType(Mage::helper('optimal')->processCardNickname($transaction->card->brand))
            ->setCcExpMonth($month)
            ->setCcExpYear($year)
            ->setCcLast4($transaction->card->lastDigits);
        $payment->save();

        $this->_redirect('checkout/onepage/success');
    }
}