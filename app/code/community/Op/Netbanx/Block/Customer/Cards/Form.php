<?php

class Op_Netbanx_Block_Customer_Cards_Form extends Mage_Core_Block_Template
{
    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = array($this->__('Month'));
        $months = array_merge($months, Mage::getSingleton('payment/config')->getMonths());
        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = array($this->__('Year'));
        $years = array_merge($years, Mage::getSingleton('payment/config')->getYears());
        return $years;
    }
}