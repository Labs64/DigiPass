<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 30.10.2015
 * Time: 9:59
 */

namespace NetLicensing;


class TokenService extends BaseEntityService
{
    public function init()
    {
        $this->nl_connect->setResponseFormat('xml');
    }

    public static function connect(NetLicensingAPI $nl_connect)
    {
        return new TokenService($nl_connect);
    }

    public function getList()
    {
        return $this->_getList($this->nl_connect);
    }

    public function get($number)
    {
        return $this->_get($number, $this->nl_connect);
    }

    public function create($token_type = 'DEFAULT', $licensee_number = '')
    {
        $token_type = strtoupper($token_type);
        if ($token_type != 'DEFAULT' && $token_type != 'SHOP') {
            throw new NetLicensingException('Wrong token type, expected DEFAULT or SHOP, given ' . $token_type);
        }

        $params['tokenType'] = $token_type;

        if ($token_type == 'SHOP') {
            $params['licenseeNumber'] = $licensee_number;
        }

        $response = $this->nl_connect->post($this->_getServiceUrlPart(), $params);
        $properties_array = NetLicensingAPI::getPropertiesByXml($response->body);

        if (empty($properties_array)) return FALSE;

        $properties = reset($properties_array);

        $token = $this->_getNewEntity();
        $token->setProperties($properties, TRUE);

        return $token;
    }


    public function delete($number)
    {
        return $this->_delete($number, $this->nl_connect);
    }

    protected function _getNewEntity()
    {
        return new Token();
    }

    protected function _getServiceUrlPart()
    {
        return '/token';
    }
} 