<?php
/**
 * @wordpress-plugin
 * Plugin Name: DigiPass
 * Plugin URI:  https://github.com/Labs64/digipass
 * Description: The best way for publishers and bloggers to monetize their content.
 * Author:      Labs64
 * Author URI:  http://www.labs64.com
 * Version:     0.1.0
 * Text Domain: digipass
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 3.5.1
 * Tested up to: 4.0
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2015 Labs64
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('DIGIPASS_DIR', plugin_dir_path(__FILE__));

// main
require_once(DIGIPASS_DIR . 'public/digipass-class-base.php');
require_once(DIGIPASS_DIR . 'public/digipass-class.php');
require_once(DIGIPASS_DIR . 'public/digipass-functions.php');

// utils
require_once(DIGIPASS_DIR . 'includes/netlicensing/netlicensing-load.php');

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array('DigiPass', 'activate'));
register_deactivation_hook(__FILE__, array('DigiPass', 'deactivate'));
register_uninstall_hook(__FILE__, array('DigiPass', 'uninstall'));

add_action('plugins_loaded', array('DigiPass', 'get_instance'));

if (is_admin()) {
    require_once(plugin_dir_path(__FILE__) . 'admin/digipass-class-admin.php');
    add_action('plugins_loaded', array('DigiPass_Admin', 'get_instance'));
}
