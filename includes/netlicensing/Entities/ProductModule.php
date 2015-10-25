<?php
namespace NetLicensing;

class ProductModule extends BaseEntity
{
    protected function init($properties)
    {
        //add Required Properties for ProductModule
        $this->_setRequiredProperty('name');
        $this->_setRequiredProperty('productNumber');
        $this->_setRequiredProperty('licensingModel');
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_setProperty('name', $name);
    }

    /**
     * @return string name
     */
    public function getName()
    {
        return $this->_getProperty('name');
    }

    /**
     * @param string $product_number
     */
    public function setProductNumber($product_number)
    {
        $this->_setProperty('productNumber', $product_number);
    }

    /**
     * @return string productNumber
     */
    public function getProductNumber()
    {
        return $this->_getProperty('product_number');
    }

    /**
     * @param string $licensingModel
     */
    public function setLicensingModel($licensingModel)
    {
        $this->_setProperty('licensingModel', ucfirst($licensingModel));
    }

    /**
     * @return string licensingModel
     */
    public function getLicensingModel()
    {
        return $this->_getProperty('licensingModel');
    }


}