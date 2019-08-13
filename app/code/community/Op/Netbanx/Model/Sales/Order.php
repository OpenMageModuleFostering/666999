<?php

class Op_Netbanx_Model_Sales_Order extends Mage_Sales_Model_Order
{

    /**
     * Function override to add event dispatch
     *
     * @return $this
     */
    public function hold()
    {
        if (!$this->canHold()) {
            Mage::throwException(Mage::helper('sales')->__('Hold action is not available.'));
        }
        $this->setHoldBeforeState($this->getState());
        $this->setHoldBeforeStatus($this->getStatus());
        $this->setState(self::STATE_HOLDED, true);

        Mage::dispatchEvent('order_hold_after', array('order' => $this));

        return $this;
    }

    /**
     * Function override to add event dispatch
     *
     * @return $this|Mage_Sales_Model_Order
     */
    public function unhold()
    {
        if (!$this->canUnhold()) {
            Mage::throwException(Mage::helper('sales')->__('Unhold action is not available.'));
        }
        $this->setState($this->getHoldBeforeState(), $this->getHoldBeforeStatus());
        $this->setHoldBeforeState(null);
        $this->setHoldBeforeStatus(null);

        Mage::dispatchEvent('order_unhold_after', array('order' => $this));

        return $this;
    }
}