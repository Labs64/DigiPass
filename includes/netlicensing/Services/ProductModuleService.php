<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 20.10.2015
 * Time: 14:34
 */

namespace NetLicensing;

use Curl;

class ProductModuleService extends BaseService
{
    /** @var $_curl Curl */
    protected $_curl = null;

    public function __construct($username, $password)
    {
        parent::__construct();

        $this->_setSecurityCode(self::BASIC_AUTHENTICATION);

        $this->_setUserName($username);
        $this->_setPassword($password);

        $this->_setHeaderRequest('application/xml');
    }


    public static function connect($username, $password)
    {
        return new ProductModuleService($username, $password);
    }

    public function get($product_module_number)
    {
        $response = $this->_request('/productmodule/' . $product_module_number);
        $response_array = $this->_getResponseArrayByXml($response->body);

        if ($response_array) {
            $properties = reset($response_array);
            if ($properties) {
                $product_module = new ProductModule($properties);
                $product_module->_update();

                return $product_module;
            }
        }

        return FALSE;
    }

    public function create(ProductModule $product_module)
    {
        if ($product_module->isValid(TRUE)) {

            if (!$product_module->isNew()) {
                throw new NetLicensingException('The ProductModule cannot be created because it is already exist.');
            }

            $response = $this->_request('/productmodule', $product_module->getProperties(), 'POST');
            $response_array = $this->_getResponseArrayByXml($response->body);

            if ($response_array) {
                $properties = reset($response_array);
                if ($properties) {
                    $product_module->setProperties($properties);
                    $product_module->_update();
                    return $product_module;
                }
            }
        }

        return FALSE;
    }

    public function update(ProductModule $product_module)
    {
        if ($product_module->isValid(TRUE)) {

            if ($product_module->isNew()) {
                throw new NetLicensingException('The ProductModule cannot be updated because it is new.');
            }

            if (!$product_module->getOldProperty('number')) {
                throw new NetLicensingException('The ProductModule cannot be updated because property "number" is missing.');
            }

            $pm_properties = $product_module->getProperties();
            $pm_old_properties = $product_module->getOldProperties();

            ksort($pm_properties);
            ksort($pm_old_properties);

            if (hash('sha256', serialize($pm_properties)) == hash('sha256', serialize($pm_old_properties))) {
                return $product_module;
            }

            $response = $this->_request('/productmodule/' . $product_module->getOldProperty('number'), $product_module->getProperties(), 'POST');

            $response_array = $this->_getResponseArrayByXml($response->body);

            if ($response_array) {
                $properties = reset($response_array);
                if ($properties) {
                    $product_module->setProperties($properties);
                    $product_module->_update();
                    return $product_module;
                }
            }
        }

        return FALSE;
    }

    public function delete($product_module_number)
    {
        return ($this->_request('/productmodule/' . $product_module_number, array(), 'DELETE'));
    }

    public function getList()
    {
        $product_modules_list = array();

        $response = $this->_request('/productmodule');
        $response_array = $this->_getResponseArrayByXml($response->body);

        if ($response_array) {
            foreach ($response_array as $properties) {
                $product_module = new ProductModule($properties);
                $product_module->_update();
                $product_modules_list[$properties['number']] = $product_module;
            }
        }

        return $product_modules_list;
    }

    public function getLastResponse()
    {
        return $this->_getLastResponse();
    }

    public function getLastResponseHeader($header_name)
    {
        return $this->_getLastResponseHeader($header_name);
    }
}