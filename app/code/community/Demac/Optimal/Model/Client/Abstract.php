<?php
/**
 * Created by PhpStorm.
 * User: amacgregor
 * Date: 05/05/14
 * Time: 6:24 PM
 */

class Demac_Optimal_Model_Client_Abstract extends Mage_Core_Model_Abstract
{
    protected $_storeId        = null;
    protected $_apiUrl         = null;


    public function __construct($parameters)
    {
        if(isset($parameters['store_id'])){
            $this->_storeId = $parameters['store_id'];
        }else {
            $this->_storeId = Mage::app()->getStore()->getId();
        }

        parent::__construct();
    }

    public function _construct()
    {
        $this->_apiUrl = $this->_getApiUrl();
    }

    /**
     * Get the API url based on the configuration
     *
     * @return string
     */
    protected function _getApiUrl()
    {
        if(Mage::getStoreConfig('payment/optimal_hosted/mode', $this->_storeId) === 'development')
        {
            $url = 'https://api.test.netbanx.com';
        }else {
            $url = 'https://api.netbanx.com';
        }
        return $url;
    }

    /**
     * @return bool|string
     *
     */
    protected function _getUserPwd()
    {
        try {
            $user = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/optimal_hosted/login', $this->_storeId));
            $pwd = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/optimal_hosted/trans_key', $this->_storeId));

            if($user != '' && $pwd != '')
            {
                return $user . ':' . $pwd;
            }else{
                Mage::throwException("Something went wrong with your api credentials");
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    protected function _checkCurlVerifyPeer($curl)
    {
        if(Mage::getStoreConfig('payment/optimal_hosted/mode', $this->_storeId) === 'development')
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        }
    }

}
