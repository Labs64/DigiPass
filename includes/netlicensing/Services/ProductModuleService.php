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

    const URL_PREFIX = '/core/v2/rest';

    /**
     * @var NetLicensingAPI
     */
    protected $nl_connect;

    public function __construct($username, $password)
    {
        $this->nl_connect = new NetLicensingAPI();
        $this->nl_connect->setSecurityCode(NetLicensingAPI::BASIC_AUTHENTICATION);
        $this->nl_connect->setUserName($username);
        $this->nl_connect->setPassword($password);
        $this->nl_connect->setResponseFormat('xml');
    }


    public static function connect($username, $password)
    {
        return new ProductModuleService($username, $password);
    }

    public function get($number)
    {
        $response = $this->nl_connect->get(self::URL_PREFIX . '/productmodule/' . $number);

        $properties_array = $this->_getPropertiesByXmlResponse($response->body);

        if (empty($properties_array)) return FALSE;

        $properties = reset($properties_array);
        $product_module = new ProductModule($properties);
        $product_module->_update();

        return $product_module;
    }

    public function create(ProductModule $product_module)
    {
        if (!$product_module->isValid()) {
            throw new NetLicensingException('Missing the required properties:' . implode(',', array_keys($product_module->getRequiredPropertiesList())));
        }

        if (!$product_module->isNew()) {
            throw new NetLicensingException('The ProductModule cannot be created because it is already exist.');
        }

        $response = $this->nl_connect->post(self::URL_PREFIX . '/productmodule/', $product_module->getProperties());
        $properties_array = $this->_getPropertiesByXmlResponse($response->body);

        if (empty($properties_array)) return FALSE;

        $properties = reset($properties_array);
        $product_module->setProperties($properties);
        $product_module->_update();

        return $product_module;
    }

    public function update(ProductModule $product_module)
    {
        if (!$product_module->isValid()) {
            throw new NetLicensingException('Missing the required properties:' . implode(',', array_keys($product_module->getRequiredPropertiesList())));
        }

        if ($product_module->isNew()) {
            throw new NetLicensingException('The ProductModule cannot be updated because it is new.');
        }

        if (!$product_module->getOldProperty('number')) {
            throw new NetLicensingException('The ProductModule cannot be updated because property "number" is missing or ProductModule is new.');
        }

        $pm_properties = $product_module->getProperties();
        $pm_old_properties = $product_module->getOldProperties();

        ksort($pm_properties);
        ksort($pm_old_properties);

        if (hash('sha256', serialize($pm_properties)) == hash('sha256', serialize($pm_old_properties))) {
            return $product_module;
        }

        $response = $this->nl_connect->post(self::URL_PREFIX . '/productmodule/' . $product_module->getOldProperty('number'), $product_module->getProperties());
        $properties_array = $this->_getPropertiesByXmlResponse($response->body);

        if (empty($properties_array)) return FALSE;

        $properties = reset($properties_array);
        $product_module->setProperties($properties);
        $product_module->_update();

        return $product_module;
    }

    public function delete($number)
    {
        $response = $this->nl_connect->delete(self::URL_PREFIX . '/productmodule/' . $number);
        return ($response);
    }

    public function getList()
    {
        $list = array();

        $response = $this->nl_connect->get(self::URL_PREFIX . '/productmodule');
        $properties_array = $this->_getPropertiesByXmlResponse($response->body);

        if ($properties_array) {
            foreach ($properties_array as $properties) {
                $product_module = new ProductModule($properties);
                $product_module->_update();
                $list[$properties['number']] = $product_module;
            }
        }

        return $list;
    }
}