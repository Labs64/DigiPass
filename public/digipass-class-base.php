<?php

/**
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2015 Labs64
 */

global $wpdb;
define('DIGIPASS_TABLE_CONNECTIONS', $wpdb->prefix . 'digipass_connections');
define('DIGIPASS_TABLE_VALIDATIONS', $wpdb->prefix . 'digipass_validations');
define('DIGIPASS_TABLE_TOKENS', $wpdb->prefix . 'digipass_tokens');

define('DIGIPASS_NLIC_BASE_URL', 'https://go.netlicensing.io/core/v2/rest');
define('DIGIPASS_NLIC_API_KEY', '31c7bc4e-90ff-44fb-9f07-b88eb06ed9dc');


abstract class BaseDigiPass
{
    /**
     * Plugin version, used for cache-busting of style and script file references.
     */
    const VERSION = '0.2.1';

    const DIGIPASS_OPTIONS = 'DIGIPASS_OPTIONS';
    const DIGIPASS_OPTION_PREFIX = 'DIGIPASS_OPTION_';

    /**
     * Unique identifier for your plugin.
     *
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     */
    protected $plugin_slug = 'digipass';

    /**
     * Return the plugin slug.
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Returns default options.
     * If you override the options here, be careful to use escape characters!
     */
    protected function _dp_get_default_options()
    {
        $default_options = array(
            'digipass_feature_protect_page' => '0',
            self::DIGIPASS_OPTION_PREFIX . 'username' => '',
            self::DIGIPASS_OPTION_PREFIX . 'password' => '',
        );

        return $default_options;
    }

    /**
     * Retrieves (and sanitises) options
     */
    protected function _dp_get_options()
    {
        $options = $this->_dp_get_default_options();
        $stored_options = get_option(self::DIGIPASS_OPTIONS);
        if (!empty($stored_options)) {
            $this->dp_sanitize_fields($stored_options);
            $options = wp_parse_args($stored_options, $options);
        }
        update_option(self::DIGIPASS_OPTIONS, $options);
        return $options;
    }

    /**
     * Retrieves single option
     */
    protected function _dp_get_single_option($name)
    {
        $options = $this->_dp_get_options();
        return $options[$name];
    }

    /**
     * Set single option value
     */
    protected function _dp_set_single_option($name, $value)
    {
        $options = $this->_dp_get_options();
        $options[$name] = $value;
        update_option(self::DIGIPASS_OPTIONS, $options);
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function dp_sanitize_fields($input)
    {
        if (isset($input[self::DIGIPASS_OPTION_PREFIX . 'apikey'])) {
            $input[self::DIGIPASS_OPTION_PREFIX . 'apikey'] = sanitize_text_field($input[self::DIGIPASS_OPTION_PREFIX . 'apikey']);
        }

        return $input;
    }

    public static function dp_get_default_licensee_number($user)
    {
        return hash('md5', 'digipass' . $user->user_login);
    }

}
