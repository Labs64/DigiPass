<?php
/**
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2014 Labs64
 */

/**
 * Write DEBUG log.
 */
if (!function_exists('dp_write_log')) {
    function dp_write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

/**
 * Shorten an URL
 *
 * @param string $url
 * @return string
 */
function dp_strip_url($url, $len = 255)
{
    $short_url = str_replace(array('http://', 'https://', 'www.'), '', $url);
    $short_url = preg_replace('/[^a-zA-Z0-9_-]/', '', $short_url);
    if (strlen($short_url) > $len) {
        $short_url = substr($short_url, 0, $len);
    }
    return $short_url;
}
