<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 1:29 PM
 */

class Demac_Optimal_Model_Method extends Mage_Payment_Model_Method_Cc
{
    protected $_code        = 'optimal';
    protected $_canSaveCc   = true;
    protected $_formBlockType = 'payment/form_ccsave';
    protected $_infoBlockType = 'payment/info_ccsave';
}