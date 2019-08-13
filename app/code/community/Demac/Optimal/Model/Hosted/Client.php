<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Allan MacGregor - Magento Practice Lead <allan@demacmedia.com>
 * Company: Demac Media Inc.
 * Date: 6/20/13
 * Time: 12:53 PM
 */

class Demac_Optimal_Model_Hosted_Client extends Demac_Optimal_Model_Client_Abstract
{
    protected $_merchantRefNum = null;
    protected $_currencyCode   = null;
    protected $_totalAmount    = null;


    const CONNECTION_RETRIES   = 3;

    public function _construct()
    {

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

        parent::_construct();
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
        $helper = Mage::helper('optimal');
        $session = Mage::getSingleton('customer/session');
        $response = json_decode($this->_callApi($url,$method,$data));
        $defaultMessage = 'Payment Gateway Error. Please contact the site admin.';

        if (isset($response->error) && !isset($response->error->code)) {
            Mage::log($response, null, 'demac_optimal.log');
            $helper->cleanMerchantCustomerId($session->getId());
            throw new Demac_Optimal_Model_Hosted_Exception($defaultMessage);
        }

        if (isset($response->error)) {
            $message = $helper->getMsgByCode($response->error->code);

            if ($message === null && isset($response->error->message)) {
                $message = $response->error->message;
            } else {
                $message = $defaultMessage;
            }

            $helper->cleanMerchantCustomerId($session->getId());

            throw new Demac_Optimal_Model_Hosted_Exception($message);
        }

        if (isset($response->transaction->errorCode)) {
            $message = $helper->getMsgByCode($response->transaction->errorCode);

            if ($message === null && !isset($response->transaction->errorCode)) {
                Mage::log($response, null, 'demac_optimal.log');
                throw new Demac_Optimal_Model_Hosted_Exception($defaultMessage);
            }

            if ($message === null) {
                $message = $response->transaction->errorMessage;
            }

            if (empty($message)) {
                $message = $defaultMessage;
            }

            if (!$session->getCustomerId()) {
                $session->addError($message);
            }

            Mage::helper('optimal')->cleanMerchantCustomerId($session->getId());

            throw new Demac_Optimal_Model_Hosted_Exception($message);
        }

        return $response;
    }

    /**
     * Returns 'Default' Error message if message by Code is not found
     *
     * @param null $code
     * @return null|string
     */
    protected function _getMsgByCode($code = null)
    {
        $message = Mage::helper('optimal')->getMsgByCode($code);
        if ($message !== null) {
            return $message;
        }

        return null;
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

        } catch (Mage_Exception $e) {
            Mage::logException($e);
            return false;
        }
        Mage::log('OPTIMAL RESPONSE (_callApi):', null, 'demac_optimal.log');
        Mage::log($curl_response, null, 'demac_optimal.log');
        return $curl_response;
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
            curl_setopt($curl, CURLOPT_HTTPHEADER, array());

            $this->_checkCurlVerifyPeer($curl);

            //set the url, number of POST vars, POST data
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            $curl_response = curl_exec($curl);

            $headers = substr($curl_response, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
            $headers = explode("\n", $headers);

            Mage::log('OPTIMAL RESPONSE (submitPayment):', null, 'demac_optimal.log');
            Mage::log($curl_response, null, 'demac_optimal.log');
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
