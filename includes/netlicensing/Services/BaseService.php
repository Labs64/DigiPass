<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 20.10.2015
 * Time: 14:57
 */

namespace NetLicensing;

use SimpleXMLElement;

abstract class BaseService
{

    protected function _getPropertiesByJsonResponse($json)
    {
        $properties = array();
        $response = json_decode($json);

        if (!empty($response->items->item)) {

            foreach ($response->items->item as $item) {
                $tmp_array = array();

                foreach ($item->property as $property) {
                    $property = (array)$property;
                    $tmp_array[$property['@name']] = $property['$'];

                }
                if (!empty($tmp_array['number'])) {
                    $properties[$tmp_array['number']] = $tmp_array;
                }
            }
        }

        return $properties;
    }

    protected function _getPropertiesByXmlResponse($xml)
    {
        $properties = array();

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
                        $properties[$tmp_array['number']] = $tmp_array;
                    }
                }
            }
        }

        return $properties;
    }
}