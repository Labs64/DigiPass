<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 26.10.2015
 * Time: 11:20
 */

namespace NetLicensing;

use Curl;

class NetLicensingAPI
{
    const BASE_URL = 'https://netlicensing.labs64.com/';

    const BASIC_AUTHENTICATION = 0;
    const API_KEY_IDENTIFICATION = 1;

    private $_username = '';
    private $_password = '';
    private $_api_key = '';
    private $_security_code = '';
    private $_vendor_number = '';

    /** @var $_curl Curl */
    private $_curl;

    private $_last_response = array();
    private $_success_required = TRUE;

    public function __construct()
    {
        $this->_curl = new Curl();
    }

    public function setUserName($username)
    {
        $this->_username = $username;
    }

    public function getUserName()
    {
        return $this->_username;
    }

    public function setPassword($password)
    {
        $this->_password = $password;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    public function setApiKey($api_key)
    {
        $this->_api_key = $api_key;
    }

    public function getApiKey()
    {
        return $this->_api_key;
    }

    public function setSecurityCode($security_flag)
    {
        if ($security_flag != self::BASIC_AUTHENTICATION && $security_flag != self::API_KEY_IDENTIFICATION) {
            throw new NetLicensingAPIException('Wrong authentication flag');
        }

        $this->_security_code = $security_flag;
    }

    public function getSecurityCode()
    {
        return $this->_security_code;
    }

    public function setVendorNumber($vendor_number)
    {
        $this->_vendor_number = $vendor_number;
    }

    public function getVendorNumber()
    {
        return $this->_vendor_number;
    }

    public function setResponseFormat($format)
    {
        switch (strtolower($format)) {
            case 'json':
                $format = 'application/json';
                break;
            case 'xml':
                $format = 'application/xml';
                break;
            case 'application/json':
                break;
            case 'application/xml':
                break;
            default:
                throw new NetLicensingAPIException(printf('Got unsupported response format %s', $format));
                break;
        }

        $this->_curl->headers['Accept'] = $format;
    }

    public function getLastResponse()
    {
        return $this->_last_response;
    }

    public function get($url, $params = array())
    {
        return $this->_request('GET', $url, $params);
    }

    public function post($url, $params = array())
    {
        return $this->_request('POST', $url, $params);
    }

    public function put($url, $params = array())
    {
        return $this->_request('PUT', $url, $params);
    }

    public function delete($url, $params = array())
    {
        return $this->_request('DELETE', $url, $params);
    }

    public function successRequestRequired($state)
    {
        $this->_success_required = ($state) ? TRUE : FALSE;
    }

    protected function _request($method, $url, $params = array())
    {
        $allowed_requests_types = array('GET', 'POST', 'PUT', 'DELETE');

        $method = strtoupper($method);
        if (!in_array($method, $allowed_requests_types)) {
            throw new NetLicensingAPIException('Invalid request type:' . $method . ', allowed requests types: GET, POST, PUT, DELETE.');
        }

        switch ($this->_security_code) {
            case self::BASIC_AUTHENTICATION:

                if (empty($this->_username)) throw new NetLicensingAPIException('Missing parameter "username" for connection');
                if (empty($this->_password)) throw new NetLicensingAPIException('Missing parameter "password" for connection');

                $this->_curl->headers['Authorization'] = 'Basic ' . base64_encode($this->_username . ":" . $this->_password);
                break;
            case self::API_KEY_IDENTIFICATION:

                if (empty($this->_api_key)) throw new NetLicensingAPIException('Missing parameter "apiKey" for connection');

                $this->_curl->headers['Authorization'] = 'Basic ' . base64_encode("apiKey:" . $this->_api_key);
                break;
            default:
                throw new NetLicensingAPIException('Missing or wrong authentication security code');
                break;
        }

        $url = str_replace(self::BASE_URL, '', $url);
        $url = self::BASE_URL . $url;

        $this->_last_response = $this->_curl->request($method, $url, $params);

        if ($this->_success_required) {
            switch ($this->_last_response->headers['Status-Code']) {
                case '200':
                    break;
                case '404':
                    break;
                default:
                    if ($this->_last_response) {
                        $status_code = $this->_last_response->headers['Status-Code'];

                        $xml = simplexml_load_string($this->_last_response->body);
                        $status_description = (string)$xml->infos->info;

                        throw new NetLicensingAPIException(sprintf('Bad response, result code %1$s: %2$s', $status_code, $status_description));
                    }else{
                        throw new NetLicensingAPIException('Can not connect to the NetLicensing server');
                    }

                    break;
            }
        }

        return $this->_last_response;
    }
}

class NetLicensingAPIException extends \Exception
{
}