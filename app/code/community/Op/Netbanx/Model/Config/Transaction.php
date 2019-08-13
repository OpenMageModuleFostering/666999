<?php

class Op_Netbanx_Model_Config_Transaction extends Mage_Core_Model_Config_Data
{
    const AUTH_VALUE = 'auth';
    const CAPT_VALUE = 'purchase';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => Op_Netbanx_Model_Method_Hosted::ACTION_AUTHORIZE,
                'label' => Mage::helper('optimal')->__('Authorize Only')
            ),
            array(
                'value' => Op_Netbanx_Model_Method_Hosted::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('optimal')->__('Authorize and Capture')
            ),
        );
    }
}