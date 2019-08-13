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
    public function formatAmount($amount)
    {
        $amount = (int)(string)((number_format($amount,2,'.','')) * 100);
        return $amount;
    }

    public function getStreetDetails($address, $position)
    {
        if(isset($address[$position]))
        {
            return $address[$position];
        } else {
            return '';
        }
    }
}