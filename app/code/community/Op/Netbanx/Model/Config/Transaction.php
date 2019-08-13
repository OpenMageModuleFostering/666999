<?php
/**
 * Demac Media
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.demacmedia.com/LICENSE-Magento.txt
 *
 * @category   Demac
 * @author     Michael Kreitzer (michael@demacmedia.com), Allan MacGregor <allan@demacmedia.com>
 * @package    Demac_CanadaPost
 * @copyright  Copyright (c) 2010-2012 Demac Media (http://www.demacmedia.com)
 * @license    http://www.demacmedia.com/LICENSE-Magento.txt
 */

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