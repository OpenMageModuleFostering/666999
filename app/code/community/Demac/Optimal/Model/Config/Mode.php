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

class Demac_Optimal_Model_Config_Mode extends Mage_Core_Model_Config_Data
{
    const DEV_VALUE = 'development';
    const PROD_VALUE = 'production';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            self::DEV_VALUE => Mage::helper('optimal')->__('Development'),
            self::PROD_VALUE => Mage::helper('optimal')->__('Production'),
        );
    }
}