<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


class Shipox_Api_Client
{
    private $_apiHostUrls = array(
        1 => array(
            "test" => "https://stagingapi.shipox.com",
            "live" => "https://prodapi.shipox.com",
        ),
        2 => array(
            "test" => "https://stagingapi.shipox.com",
            "live" => "https://prodapi.safe-arrival.com",
        ),
    );

    private $_authenticateUrl = "/api/v1/customer/authenticate";
    private $_countryListUrl = "/api/v1/country/list";
    private $_cityListUrl = "/api/v1/cities";
    private $_cityItemUrl = "/api/v1/city/";
    private $_packageMeuListUrl = "/api/v2/package-menu";
    private $_priceListUrl = "/api/v1/packages/prices";
    private $_priceListUrlV2 = "/api/v2/packages/plugin/prices/";
    private $_createOrderUrl = "/api/v1/customer/order";
    private $_createOrderV2Url = "/api/v2/customer/order";
    private $_marketplaceUrl = "/api/v1/marketplace";
    private $_getOrderDetailsUrl = "/api/v1/customer/order/order_number/";
    private $_getCityByName = "/api/v1/city_by_name";
    private $_getLocationByAddress = "/api/v1/coordinate_by_address";
    private $_getAirwaybill = "/api/v1/customer/order/%s/airwaybill";
    private $_updateOrderStatus = "/api/v1/customer/order/{id}/status_update";
    private $_getServiceTypes = "/api/v1/admin/service_types";
    private $_serviceConfig = array();
    private $_merchantInfo = array();
    private $_merchantConfig = array();
    public $_timeout = 10;


    /**
     * WingApiClient constructor.
     */
    function __construct()
    {
        $this->init();
    }

    /**
     * Initialize
     */
    private function init()
    {
        $this->_serviceConfig = get_option('wing_service_config');
        $this->_merchantInfo = get_option('wing_merchant_address');
        $this->_merchantConfig = get_option('wing_merchant_config');
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * @return string
     */
    public function getAPIBaseURl()
    {
        $instance = 1;
        if(isset($this->_serviceConfig['instance']) && isset($this->_apiHostUrls[$this->_serviceConfig['instance']])) $instance = $this->_serviceConfig['instance'];

        if($this->_serviceConfig['test_mode'] == 1) {
            return $this->_apiHostUrls[$instance]['test'];
        }

        return $this->_apiHostUrls[$instance]['live'];
    }

    /**
     * @param null $data
     * @return null
     */
    public function authenticate($data = null)
    {
        if ($data == null) {
            $data = array(
                'username' => $this->_merchantConfig['merchant_username'],
                'password' => $this->_merchantConfig['merchant_password'],
                'remember_me' => true
            );
        }


        $response = $this->sendRequest($this->_authenticateUrl, 'post', $data, true);
        return $response;
    }

    /**
     * Check Token is expired or not, if expired reauthorize Wing and refresh Token
     * @return bool
     */
    public function checkTokenExpired()
    {
        if ((time() - $this->_merchantConfig['last_login_date']) > 100) {
            if (is_null($this->_merchantConfig['merchant_username']) && is_null($this->_merchantConfig['merchant_password'])) {
                shipox()->log->write("Check Token Expired Function: Merchant option is empty", 'error');
                return false;
            }

            $time_request = time();
            $response = $this->authenticate();

            if ($response['success']) {
                $options['merchant_token'] = $response['data']['id_token'];
                $options['merchant_username'] = $this->_merchantConfig['merchant_username'];
                $options['merchant_password'] = $this->_merchantConfig['merchant_password'];
                $options['last_login_date'] = $time_request;

                update_option('wing_merchant_config', $options);

                $this->init();

                shipox()->wing["api-helper"]->updateCustomerMarketplace();

                return true;
            }

            shipox()->log->write($response['data']['code'] . ": " . $response['data']['message'], 'error');

            return false;
        }

        return true;
    }


    /**
     * @param $url
     * @param string $requestMethod
     * @param null $data
     * @param bool $getToken
     * @return array
     */
    public function sendRequest($url, $requestMethod = 'get', $data = null, $getToken = false)
    {
        $response = array();
        $response['success'] = false;

        if (!$getToken) {
            $isTokenValid = $this->checkTokenExpired();
            if (!$isTokenValid) {
                $response['data']['code'] = 'error.validation';
                $response['data']['message'] = __('Token Expired and cannot re-login to the System', 'wing');
                return $response;
            }
        }

        $apiURL = $this->getAPIBaseURl();

        $requestURL = $apiURL . $url;
        $ch = curl_init($requestURL);

        switch ($requestMethod) {
            case 'get':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
            case 'post':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $json = $data ? json_encode($data) : '';
        $curlHeader = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
            'RemoteAddr: ' . $_SERVER['REMOTE_ADDR']
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $token = $this->_merchantConfig['merchant_token'];

        if (!$getToken) {
            if ($token) {
                $curlHeader[] = 'Authorization: ' . 'Bearer ' . $token;
                $curlHeader[] = 'Accept: ' . 'application/json';
            } else {
                $curlHeader[] = 'Accept: ' . '*/*';
            }
        } else {
            $curlHeader[] = 'Accept: ' . '*/*';
        }

        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $curl_response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(curl_errno($ch)){
            shipox()->log->write(curl_error($ch), 'curl-error');
            shipox()->log->write($json, 'curl-error');
            shipox()->log->write($httpCode, 'curl-error');
        }

        $json_result = json_decode($curl_response, true);

        shipox()->log->write($requestURL, 'curl-api');
        shipox()->log->write($json, 'curl-api');
        shipox()->log->write($httpCode, 'curl-api');
        shipox()->log->write($curl_response, 'curl-api');
        shipox()->log->write($json_result, 'curl-api');

        switch (intval($httpCode)) {
            case 200:
            case 201:
                $response['success'] = true;
                if ($json_result['status'] == 'success')
                    $response['data'] = $json_result['data'];
                else
                    $response['data'] = $json_result;
                break;

            default:
                $response['data'] = $json_result;

                shipox()->log->write($requestURL, 'api-error');
                shipox()->log->write($json, 'api-error');
                shipox()->log->write($httpCode, 'api-error');
                shipox()->log->write($json_result, 'api-error');

                break;
        }

        return $response;
    }

    /**
     * @return null
     */
    public function wingCountries()
    {
        $response = $this->sendRequest($this->_countryListUrl);
        return $response;
    }

    /**
     * @param bool $isDomestic
     * @return null
     */
    public function getCityList($isDomestic = false)
    {
        $data = array(
            'is_uae' => $isDomestic
        );

        $response = $this->sendRequest($this->_cityListUrl. "?" . http_build_query($data), 'get');

        return $response ? $response['data'] : $response;
    }

    /**
     * @param $cityId
     * @return array
     */
    public function getCity($cityId)
    {
        $response = $this->sendRequest($this->_cityItemUrl . $cityId);
        return $response;
    }

    /**
     * @param string $params
     * @return null
     */
    public function wingPackageMenuList($params = '')
    {
        $response = $this->sendRequest($this->_packageMeuListUrl . $params);
        return $response;
    }

    /**
     * @param string $params
     * @return null
     */
    public function wingCalcPrices($params = '')
    {
        $response = $this->sendRequest($this->_priceListUrl . $params);
        return $response;
    }

    /**
     * @param $data
     * @return null
     */
    public function wingCreateOrder($data)
    {
        $response = $this->sendRequest($this->_createOrderUrl, 'post', $data);
        return $response;
    }

    /**
     * @param string $orderNumber
     * @return null
     * @internal param string $params
     */
    public function wingGetOrderDetails($orderNumber = '')
    {
        $response = $this->sendRequest($this->_getOrderDetailsUrl . $orderNumber);
        return $response;
    }

    /**
     * @param $cityName
     * @return array|mixed
     */
    public function wingIsValidCity($cityName)
    {
        $data = array(
            'city_name' => $cityName
        );
        $url = $this->_getCityByName . "?" . http_build_query($data);

        $response = $this->sendRequest($url, 'get');
        return $response;
    }


    /**
     * Get Order Aiwaybill
     * @param $orderId
     * @return array
     */
    public function getAirwaybill($orderId) {
        $response = $this->sendRequest(sprintf($this->_getAirwaybill, $orderId));
        return $response;
    }

    /**
     * @return null
     */
    public function getCustomerMarketplace()
    {
        $response = $this->sendRequest($this->_marketplaceUrl);
        return $response;
    }

    /**
     * @param $data
     * @return null
     */
    public function getLocationByAddress($data)
    {
        $url = $this->_getLocationByAddress . "?" . http_build_query($data);
        $response = $this->sendRequest($url, 'get');
        return $response;
    }


    /**
     * @param $orderId
     * @param null $data
     * @return array|mixed
     */
    public function updateOrderStatus($orderId, $data = null)
    {
        $response = $this->sendRequest(str_replace("{id}", $orderId, $this->_updateOrderStatus), 'put', $data);
        return $response;
    }

    /**
     * Get Dynamic Service Types
     * @return null
     */
    public function getAllServiceTypes()
    {
        $response = $this->sendRequest($this->_getServiceTypes, 'get');
        return $response;
    }

    /**
     * @param $data
     * @return array
     */
    public function getPackagePricesV2($data) {
        $url = $this->_priceListUrlV2 . "?" . http_build_query($data);
        $response = $this->sendRequest($url, 'get');
        return $response;
    }

    /**
     * @param $data
     * @return null
     */
    public function wingCreateOrderV2($data)
    {
        $response = $this->sendRequest($this->_createOrderV2Url, 'post', $data);
        return $response;
    }
}