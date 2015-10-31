<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 30.10.2015
 * Time: 10:03
 */

namespace NetLicensing;


class Token extends BaseEntity
{
    public function __construct(array $properties = array())
    {
        $this->_setProperties($properties);
    }

    public function getNumber($default = '')
    {
        return $this->_getProperty('number', $default);
    }

    public function getActive()
    {
        return ($this->_getProperty('active'));
    }

    public function getExpirationTime($default = '')
    {
        return $this->_getProperty('expirationTime', $default);
    }

    public function getTokenType($default = '')
    {
        return $this->_getProperty('tokenType', $default);
    }

    public function getShopUrl($default = '')
    {
        return $this->_getProperty('shopURL', $default);
    }

    public function getLicenseeNumber($default = '')
    {
        return $this->_getProperty('licenseeNumber', $default);
    }

    public function getVendorNumber($default = '')
    {
        return $this->_getProperty('vendorNumber', $default);
    }
} 