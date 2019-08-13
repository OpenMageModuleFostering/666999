<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 12:53 PM
 */

class Op_Netbanx_Model_Creditcard_Client extends Op_Netbanx_Model_Client_Abstract
{
    protected $_merchantRefNum = null;
    protected $_currencyCode   = null;
    protected $_totalAmount    = null;
    protected $_restEndpoints  = array();

    const CONNECTION_RETRIES   = 3;

    public function _construct()
    {
        // Initialize methods array
        $this->_restEndpoints = array(
            'delete'  => 'customer/v1/cards/%s',
            'get'     => 'customer/v1/cards/%s',
        );

        parent::_construct();

    }

    /**
     *
     * Create an Profile in Netbanks.
     *
     * @param $data (
     *    merchantRefNum = (string) MagentoOrderId
     *    currencyCode   = (ISO4217) Order currency code
     *    totalAmount    = (int) Order Grand Total
     *    customerIP     = (string) remote_ip
     *
     *    customerNotificationEmail = (string) Order customer email
     *    merchantNotificationEmail = (string) Order contact email
     * )
     * @return bool|mixed
     */
    public function createProfile($data)
    {
        $mode   = 'POST';
        $url    = $this->_getUrl('create');

        return $this->callApi($url,$mode,$data);
    }

    /**
     *
     * Cancel an Order in Netbanks
     *
     * @param $id
     * @internal param $data ( id = netbanksOrderId )
     *
     * @return bool|mixed
     */
    public function deleteProfile($id)
    {
        $mode = 'DELETE';
        $url = $this->_getUrl('delete', $id);

        return $this->callApi($url,$mode);
    }


    /**
     * Mapping of the RESTFul Api
     *
     * Create a Profile     - customer/v1/profiles                      [POST]
     * Delete a Profile     - customer/v1/profiles/{id}                 [DELETE]
     * Get a Profile        - customer/v1/profiles/{id}                 [GET]
     *
     * @param $method
     * @param $url
     * @param $data = Array(id,content)
     * @return bool|mixed
     */
    protected function callApi($url, $method, $data = array())
    {
        $response = json_decode($this->_callApi($url,$method,$data));

        if(isset($response->error))
        {
            Mage:log('Netbanks Returned Error: ' . $response->error->message,null,'DemacOptimal_error.log');
            Mage::throwException($response->error->message);
            return false;
        }
        if(isset($response->transaction->errorCode))
        {
            $session = Mage::getSingleton('customer/session');
            if (!$session->getCustomerId()) {
                Mage::getSingleton('customer/session')->addError($response->transaction->errorMessage);
            }
            Mage::throwException($response->transaction->errorMessage);
            return false;
        }
        return $response;
    }

    /**
     * Makes CURL requests to the netbanks api
     *
     * @param $url
     * @param $mode
     * @param array $data
     * @return mixed
     */
    protected function _callApi($url,$mode,$data = array())
    {
        $helper = Mage::helper('optimal');
        $data = json_encode($data);

        try {
            $curl = curl_init($url);
            $headers[] = "Content-Type: application/json";

            $this->_checkCurlVerifyPeer($curl);


            curl_setopt($curl, CURLOPT_USERPWD, $this->_getUserPwd());
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            switch ($mode) {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "DELETE":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "GET":
                    //hosted/v1/orders/{id}
                    break;
                default:
                    Mage::throwException("{$mode} mode was not recognized. Please one of the valid REST actions GET, POST, PUT, DELETE");
                    break;
            }

            $curl_response = curl_exec($curl);
            curl_close($curl);

            // Check if the response is false
            if($curl_response === false)
            {
                Mage::throwException("Something went wrong while trying to retrieve the response from the REST api");
            }

            // Check if the response threw an error
            if($curl_response === false)
            {
                Mage::throwException("Something went wrong while trying to retrieve the response from the REST api");
            }


        } catch (Mage_Exception $e) {
            Mage::logException($e);
            return false;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $curl_response;
    }


    /**
     * Build the RESTful url
     *
     * @param $method
     * @param null $id
     * @return string
     */
    protected function _getUrl($method,$id = null)
    {
        return $this->_apiUrl . '/' . sprintf($this->_restEndpoints[$method],$id);
    }
}
