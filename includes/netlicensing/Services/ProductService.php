<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 28.10.2015
 * Time: 5:39
 */

namespace NetLicensing;


class ProductService extends BaseEntityService {

    public function init()
    {
        $this->nl_connect->setResponseFormat('xml');
    }

    public static function connect(NetLicensingAPI $nl_connect)
    {
        return new ProductService($nl_connect);
    }

    public function getList()
    {
        return $this->_getList($this->nl_connect);
    }

    /**
     * @param $number
     * @return ProductModule|false
     * @throws NetLicensingException
     */
    public function get($number)
    {
        return $this->_get($number, $this->nl_connect);
    }

    public function create(Product $product_module)
    {
        return $this->_create($product_module, $this->nl_connect);
    }

    public function update(Product $product_module)
    {
        return $this->_update($product_module, $this->nl_connect);
    }

    public function delete($number, $force_cascade = FALSE)
    {
        return $this->_delete($number, $this->nl_connect, $force_cascade);
    }

    protected function _getNewEntity()
    {
        return new Product();
    }

    protected function _getServiceUrlPart()
    {
        return '/product';
    }
} 