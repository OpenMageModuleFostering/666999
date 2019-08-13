<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 1:29 PM
 */

class Demac_Optimal_Model_Method_Hosted extends Mage_Payment_Model_Method_Cc
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

    protected $_formBlockType   = 'payment/form_ccsave';
    protected $_infoBlockType   = 'payment/info_ccsave';


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
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        return $this->_canRefund;
    }

    /**
     * Check void availability
     *
     * @param Varien_Object $payment
     * @internal param \Varien_Object $invoicePayment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        return $this->_canVoid;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
        }

        try {

            $additionalInformation = $payment->getAdditionalInformation();
            if (isset($additionalInformation['transaction'])) {
                $orderData = unserialize($additionalInformation['order']);
                $payment->setTransactionId($orderData['id']);
                $payment->hasIsTransactionClosed(true);
                $payment->setIsTransactionClosed(false);
            }

            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
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
        $helper = Mage::helper('optimal');
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payment')->__('Invalid amount for capture.'));
        }

        try {
            $additionalInformation = $payment->getAdditionalInformation();

            if (isset($additionalInformation['transaction']))
            {

                $paymentData = unserialize($additionalInformation['transaction']);
                $orderData = unserialize($additionalInformation['order']);
                $client = Mage::getModel('optimal/hosted_client');

                $payment->setAmount($amount);
                $order          = $payment->getOrder();


                /**
                 * Commenting code because OPTIMAL API is broken
                 * For the record going live right now is bullshit
                 */

//                if($paymentData->status == 'held')
//                {
//                    $data = array(
//                        "transaction" => array(
//                            "status"    => "success"
//                        )
//                    );
//
//                    $updateResponse = $client->updateOrder($data,$orderData['id']);
//                    $order->addStatusHistoryComment(
//                        'Trans Type: Update<br/>' .
//                        'Confirmation Number: ' . $updateResponse->transaction->confirmationNumber .'<br/>' .
//                        'Transaction Status: ' . $updateResponse->transaction->status .'<br/>'
//                    );
//
//                    if($updateResponse->transaction->status != 'success'){
//                        Mage::throwException('There was a problem releasing the Transaction. Please contact support@demacmedia.com');
//                    }
//                }

                $data = array(
                    'amount' => (int)$helper->formatAmount($amount),
                    'merchantRefNum' => (string)$paymentData->merchantRefNum
                );

                $response       = $client->settleOrder($data, $orderData['id']);
                $orderStatus    = $client->retrieveOrder($orderData['id']);
                $transaction    = $orderStatus->transaction;

                $associatedTransactions = $transaction->associatedTransactions;

                $payment->setAdditionalInformation('transaction', serialize($transaction));

                $order->addStatusHistoryComment(
                    'Trans Type: ' . $response->authType .'<br/>' .
                    'Confirmation Number: ' . $response->confirmationNumber .'<br/>' .
                    'Transaction Amount: ' . $response->amount/100 .'<br/>'
                );

                return $this;

            } else {
                Mage::throwException('Transaction information is not properly set. Please contact support@demacmedia.com');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
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
                $client = Mage::getModel('optimal/hosted_client');

                $paymentData    = unserialize($additionalInformation['transaction']);
                $orderData      = unserialize($additionalInformation['order']);

                $response = $client->cancelOrder($orderData['id']);

                $payment
                    ->setIsTransactionClosed(1)
                    ->setShouldCloseParentTransaction(1);

                $order = $payment->getOrder();

                $order->addStatusHistoryComment(
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
            return false;
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
                $client = Mage::getModel('optimal/hosted_client');

                $paymentData    = unserialize($additionalInformation['transaction']);
                $orderData      = unserialize($additionalInformation['order']);

                $data = array(
                    'amount'            => (int)$helper->formatAmount($amount),
                    'merchantRefNum'    => (string)$paymentData->merchantRefNum
                );

                $response = $client->refundOrder($data,$paymentData->associatedTransactions[0]->reference);

                $order = $payment->getOrder();

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
            return false;
        }

        return $this;
    }

}