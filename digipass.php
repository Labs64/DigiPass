<?php
/**
 * @wordpress-plugin
 * Plugin Name: DigiPass
 * Plugin URI:  https://github.com/Labs64/digipass
 * Description: The best way for publishers and bloggers to monetize their content.
 * Author:      Labs64
 * Author URI:  http://www.labs64.com
 * Version:     0.9.0
 * Text Domain: digipass
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 3.5.1
 * Tested up to: 3.9.2
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2014 Labs64
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Plugin version, used for cache-busting of style and script file references.
 */
define('DP_VERSION', '0.9.0');

/**
 * Unique identifier for your plugin.
 *
 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
 * match the Text Domain file header in the main plugin file.
 */
define('DP_SLUG', 'digipass');

// main
require_once(plugin_dir_path(__FILE__) . 'digipass-class.php');
require_once(plugin_dir_path(__FILE__) . 'digipass-functions.php');
require_once(plugin_dir_path(__FILE__) . 'options.php');
// util
require_once(plugin_dir_path(__FILE__) . '/php/netlicensing/netlicensing.php');
require_once(plugin_dir_path(__FILE__) . '/php/curl/curl.php');
require_once(plugin_dir_path(__FILE__) . '/php/curl/curl_response.php');


// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array('DigiPass', 'activate'));
register_deactivation_hook(__FILE__, array('DigiPass', 'deactivate'));

add_action('plugins_loaded', array('DigiPass', 'get_instance'));
