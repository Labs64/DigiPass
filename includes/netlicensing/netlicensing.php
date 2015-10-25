<?php

/**
 * A basic NetLicensing client
 *
 * @package netlicensing
 * @author Labs64 <info@labs64.com>
 **/
if (!class_exists('NetLicensing')) {

    class NetLicensing
    {

        const NLIC_BASE_URL = 'https://netlicensing.labs64.com/core/v2/rest';

        private $curl = null;
        private $api_key = '';
        private $username = '';
        private $password = '';

        /**
         * Initializes a NetLicensing object
         **/
        function __construct()
        {
            $user_agent = 'NetLicensing/PHP ' . PHP_VERSION . ' (http://netlicensing.labs64.com)' . '; ' . $_SERVER['HTTP_USER_AGENT'];

            $this->curl = new Curl();
            $this->curl->headers['Accept'] = 'application/json';
            $this->curl->user_agent = $user_agent;
        }

        /**
         * Validates active licenses of the licensee
         *
         * Returns a object containing licensee validation result
         *
         * @param string $productNumber
         * @param string $licenseeNumber
         * @param string $licenseeName
         * @return licensee validation result
         **/
        function validate($productNumber, $licenseeNumber, $licenseeName = '')
        {

            $this->curl->headers['Authorization'] = 'Basic ' . base64_encode("apiKey:" . $this->api_key);

            if (empty($licenseeName)) {
                $licenseeName = $licenseeNumber;
            }
            $params = array(
                'productNumber' => $productNumber,
                'licenseeName' => $licenseeName,
            );
            $url = self::NLIC_BASE_URL . '/licensee/' . $licenseeNumber . '/validate';

            $response = $this->curl->get($url, $params);

            return $response->body;
        }

        /**
         * Return a list of all product modules for the current vendor.
         **/
        function get_product_module_service_list()
        {

            $this->curl->headers['Authorization'] = 'Basic ' . base64_encode($this->username . ":" . $this->password);

            $url = self::NLIC_BASE_URL . '/productmodule';

            $response = $this->curl->get($url);

            return $response->body;
        }

        /**
         * Set apiKey for API Key Identification
         **/
        public function set_api_key_identification($api_key)
        {
            $this->api_key = $api_key;
            return $this;
        }

        /**
         * Set username and password for Basic Authentication
         **/
        public function set_basic_authentication($username, $password)
        {
            $this->username = $username;
            $this->password = $password;
            return $this;
        }
    }

}