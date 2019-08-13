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
}
