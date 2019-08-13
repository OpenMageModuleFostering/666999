<?php

class Demac_Optimal_Block_Customer_Cards_Grid extends Mage_Core_Block_Template
{
    public $profiles = null;

    /**
     * Check for existing optimal profiles
     *
     * @return bool
     */
    public function hasOptimalProfiles()
    {
        $session = Mage::getSingleton('customer/session');
        $customerId = $session->getId();
        if (isset($customerId))
        {
            $profiles = Mage::getModel('optimal/creditcard')
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('is_deleted', false);
            if($profiles->count() >= 1)
            {
                $this->profiles = $profiles;
                return true;
            }
        }

        return false;
    }
}