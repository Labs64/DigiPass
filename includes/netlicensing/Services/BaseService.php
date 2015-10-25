<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 20.10.2015
 * Time: 14:57
 */

namespace NetLicensing;

use Curl;
use SimpleXMLElement;

abstract class BaseService
{
    const BASE_URL = 'https://netlicensing.labs64.com/core/v2/rest';

    const BASIC_AUTHENTICATION = 0;
    const API_KEY_IDENTIFICATION = 1;

    private $_username = '';
    private $_password = '';
    private $_api_key = '';
    private $_security_code = null;
    private $_vendor_number = '';

    protected $_curl = null;

    private $_last_response = array();

    public function __construct()
    {
        $this->_curl = new Curl();
    }

    protected function _setUserName($username)
    {
        $this->_username = $username;
    }

    protected function _getUserName()
    {
        return $this->_username;
    }

    protected function _setPassword($password)
    {
        $this->_password = $password;
    }

    protected function _getPassword()
    {
        return $this->_password;
    }

    protected function _setApiKey($api_key)
    {
        $this->_api_key = $api_key;
    }

    protected function _getApiKey()
    {
        return $this->_api_key;
    }

    protected function _setSecurityCode($security_flag)
    {
        if ($security_flag != self::BASIC_AUTHENTICATION && $security_flag != self::API_KEY_IDENTIFICATION) {
            throw new NetLicensingException('Wrong authentication flag');
        }

        $this->_security_code = $security_flag;
    }

    protected function _getSecurityCode()
    {
        return $this->_security_code;
    }

    protected function _setVendorNumber($vendor_number)
    {
        $this->_vendor_number = $vendor_number;
    }

    protected function _getVendorNumber()
    {
        return $this->_vendor_number;
    }

    protected function _setHeaderRequest($request_type)
    {
        $this->_curl->headers['Accept'] = $request_type;
    }

    protected function _request($url, array $params = array(), $method = 'GET')
    {

        if (!$this->_curl) {
            $this->_curl = new Curl();
        }

        if ($this->_security_code !== self::BASIC_AUTHENTICATION && $this->_security_code !== self::API_KEY_IDENTIFICATION) {
            throw new NetLicensingException('Missing or wrong authentication flag');
        }

        $authorization = '';
        if ($this->_security_code == self::BASIC_AUTHENTICATION) $authorization = 'Basic ' . base64_encode($this->_username . ":" . $this->_password);
        if ($this->_security_code == self::API_KEY_IDENTIFICATION) $authorization = 'Basic ' . base64_encode("apiKey:" . $this->_api_key);

        $allowed_requests_types = array('GET', 'POST', 'PUT', 'DELETE');

        $method = strtoupper($method);
        if (!in_array($method, $allowed_requests_types)) {
            throw new NetLicensingException('Invalid curl request type:' . $method . ', allowed requests types: GET, POST, PUT, DELETE.');
        }

        $this->_curl->headers['Authorization'] = $authorization;
        $response = $this->_curl->request($method, self::BASE_URL . $url, $params);

        if ($response->headers['Status-Code'] != 200) {
            $xml=  simplexml_load_string($response->body);
            $message = (string)$xml->infos->info;
            throw new NetLicensingException($response->headers['Status'] .':'. $message);
        }

        return $this->_last_response = $response;
    }

    protected function _getLastResponse()
    {
        return $this->_last_response;
    }

    protected function _getLastResponseHeader($header_name)
    {
        return (!empty($this->_last_response->headers[$header_name])) ? $this->_last_response->headers[$header_name] : '';
    }

    protected function _getResponseArrayByJson($response)
    {
        $response_array = array();
        $response = json_decode($response);

        if (!empty($response->items->item)) {

            foreach ($response->items->item as $item) {
                $properties = array();

                foreach ($item->property as $property) {
                    $property = (array)$property;
                    $properties[$property['@name']] = $property['$'];

                }
                if (!empty($properties['number'])) {
                    $response_array[$properties['number']] = $properties;
                }
            }
        }

        return $response_array;
    }

    protected function _getResponseArrayByXml($xml)
    {
        $response_array = array();

        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }

        if (!empty($xml->items->item)) {
            foreach ($xml->items->item as $item) {
                if ($item->property) {
                    $tmp_array = array();
                    foreach ($item->property as $property) {
                        $name = (string)$property['name'];
                        $value = (string)$property;
                        $tmp_array[$name] = $value;
                    }
                    if (!empty($tmp_array['number'])) {
                        $response_array[$tmp_array['number']] = $tmp_array;
                    }
                }
            }
        }

        return $response_array;
    }


}