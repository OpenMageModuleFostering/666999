<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 12:53 PM
 */

class Demac_Optimal_Model_Hosted_Client extends Mage_Core_Model_Abstract
{
    protected $_merchantRefNum = null;
    protected $_currencyCode   = null;
    protected $_totalAmount    = null;
    protected $_apiUrl         = null;
    protected $_restEndpoints  = array();

    const CONNECTION_RETRIES   = 3;

    public function _construct()
    {
        $this->_apiUrl = $this->_getApiUrl();

        // Initialize methods array
        $this->_restEndpoints = array(
            'create'  => 'hosted/v1/orders',
            'cancel'  => 'hosted/v1/orders/%s',
            'update'  => 'hosted/v1/orders/%s',
            'info'    => 'hosted/v1/orders/%s',
            'refund'  => 'hosted/v1/orders/%s/refund',
            'settle'  => 'hosted/v1/orders/%s/settlement',
            'resend'  => 'hosted/v1/orders/%s/resend_callback',
            'report'  => 'hosted/v1/orders',
            'rebill'  => 'hosted/v1/orders/%s',
        );
    }

    /**
     *
     * Create an Order in Netbanks.
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
    public function createOrder($data)
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
    public function cancelOrder($id)
    {
        $mode = 'DELETE';
        $url = $this->_getUrl('cancel', $id);

        return $this->callApi($url,$mode);
    }

    /**
     *
     * Update Order in Netbanks
     *
     * @param $data
     */
    public function updateOrder($data,$id)
    {
        $mode = 'PUT';
        $url = $this->_getUrl('update',$id);

        return $this->callApi($url,$mode,$data);
    }

    /**
     *
     * Retrieve Order Information from Netbanks
     *
     * @param $id
     * @internal param $data
     * @return bool|mixed
     */
    public function retrieveOrder($id)
    {
        $mode = 'GET';
        $url = $this->_getUrl('info',$id);

        return $this->callApi($url,$mode);
    }

    /**
     *
     * Refund order in Netbanks
     *
     * @param $data
     * @param $id
     * @return bool|mixed
     */
    public function refundOrder($data,$id)
    {
        $mode = 'POST';
        $url = $this->_getUrl('refund',$id);

        return $this->callApi($url,$mode,$data);
    }

    /**
     *
     * Settle an order in Netbanks
     *
     * @param $data
     * @param $id
     * @return bool|mixed
     */
    public function settleOrder($data, $id)
    {
        $mode = 'POST';
        $url = $this->_getUrl('settle', $id);

        return $this->callApi($url,$mode,$data);
    }

    /**
     *
     * Get an order report form Netbanks
     *
     */
    public function orderReport()
    {
        $mode = 'GET';
        $url = $this->_getUrl('report');

        return $this->callApi($url,$mode);
    }

    /**
     *
     * Resend Callback url to Netbanks
     *
     * @param $data
     * @return bool|mixed
     */
    public function resendCallback($data)
    {
        $mode = 'GET';
        $url = $this->_getUrl('resend');

        return $this->callApi($url,$mode);
    }

    /**
     *
     * Rebill an order in Netbanks
     *
     * @param $data
     * @return bool|mixed
     */
    public function rebillOrder($data)
    {
        $mode = 'POST';
        $url = $this->_getUrl('rebill');

        return $this->callApi($url,$mode);
    }

    /**
     * Get the API url based on the configuration
     *
     * @return string
     */
    protected function _getApiUrl()
    {
        if(Mage::getStoreConfig('payment/optimal_hosted/mode') === 'development')
        {
            $url = 'https://api.test.netbanx.com';
        }else {
            $url = 'https://api.netbanx.com';
        }
        return $url;
    }

    /**
     * Mapping of the RESTFul Api
     *
     * Create an Order      - hosted/v1/orders                      [POST]
     * Cancel an Order      - hosted/v1/orders/{id}                 [DELETE]
     * Update an Order      - hosted/v1/orders/{id}                 [PUT]
     * Get an Order         - hosted/v1/orders/{id}                 [GET]
     * Refund an Order      - hosted/v1/orders/{id}/settlement      [POST]
     * Get an Order Report  - hosted/v1/orders                      [GET]
     * Resend Callbackk     - hosted/v1/orders/{id}/resend_callback [GET]
     * Process a Rebill     - hosted/v1/orders/{id}                 [POST]
     *
     * @param $method
     * @param $url
     * @param $data = Array(id,content)
     * @return bool|mixed
     */
    protected function callApi($url, $method, $data = array())
    {

        $response = json_decode($this->_callApi($url,$method,$data));

        // Do I need extra logic here ?
        // Yes I do
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
                    Mage::throwException($this->__("{$mode} mode was not recognized. Please one of the valid REST actions GET, POST, PUT, DELETE"));
                    break;
            }

            $curl_response = curl_exec($curl);
            curl_close($curl);

            // Check if the response is false
            if($curl_response === false)
            {
                Mage::throwException($this->__("Something went wrong while trying to retrieve the response from the REST api"));
            }

            // Check if the response threw an error
            if($curl_response === false)
            {
                Mage::throwException($this->__("Something went wrong while trying to retrieve the response from the REST api"));
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
     * @return bool|string
     *
     * TODO: TEST MULTI STORE SCENARIOS
     *
     */
    protected function _getUserPwd()
    {
        try {
            $user = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/optimal_hosted/login'));
            $pwd = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/optimal_hosted/trans_key'));

            if($user != '' && $pwd != '')
            {
                return $user . ':' . $pwd;
            }else{
                Mage::throwException($this->__("Something went wrong with your api credentials"));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }
    

    /**
     * @param $url
     * @param $data
     * @return bool
     */
    public function submitPayment($url,$data)
    {
        $data_string = '';

        try {
            $curl = curl_init($url);

            //url-ify the data for the POST
            foreach($data as $key=>$value)
            {
                $data_string .= $key.'='.$value.'&';
            }

            $data_string = rtrim($data_string, '&');
            curl_setopt($curl, CURLOPT_HTTPHEADER, 0);

            //set the url, number of POST vars, POST data
            curl_setopt($curl,CURLOPT_URL, $url);
            curl_setopt($curl,CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data_string);
            $curl_response = curl_exec($curl);
            curl_close($curl);
            return true;

        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
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
