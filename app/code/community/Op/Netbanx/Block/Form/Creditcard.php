<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Payment
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Op_Netbanx_Block_Form_Creditcard extends Mage_Payment_Block_Form_Cc
{
    public $profiles = null;
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('optimal/form/creditcard.phtml');
    }

    /**
     * Check for the existing optimal profiles
     *
     * @return bool
     */
    public function hasOptimalProfiles()
    {
        $session = Mage::getSingleton('customer/session');
        $customerId = $session->getId();
        if (isset($customerId))
        {
            $merchCustId = Mage::helper('optimal')->getMerchantCustomerId($customerId);
            if (!$merchCustId) {
                return false;
            }

            $merchantCustomerId = $merchCustId['merchant_customer_id'];

            $profiles = Mage::getModel('optimal/creditcard')
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('merchant_customer_id', $merchantCustomerId)
                ->addFieldToFilter('is_deleted', false);

            if($profiles->count() >= 1)
            {
                $this->profiles = $profiles;
                return true;
            }
        }

        $this->profiles = array();

        return false;
    }

    public function getStoreId()
    {
        return Mage::app()->getStore()->getStoreId();
    }

    /**
     * Check if profile saving is enabled
     *
     * @return bool
     */
    public function canSaveProfiles()
    {
        $session = Mage::getSingleton('customer/session');
        $profilesEnabled = Mage::getStoreConfig('payment/optimal_profiles/active', $this->getStoreId());
        $checkoutMethod = Mage::getModel('checkout/cart')->getQuote()->getCheckoutMethod();
        if (($session->getCustomerId() || $checkoutMethod == 'register') && $profilesEnabled)
        {
            return true;
        }
        return false;
    }

    public function skip3D()
    {
        return Mage::getStoreConfig('payment/optimal_hosted/skip3D', $this->getStoreId());
    }

    public function allowInterac()
    {
        return Mage::getStoreConfig('payment/optimal_hosted/allow_interac', $this->getStoreId());
    }
}
