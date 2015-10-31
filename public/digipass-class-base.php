<?php

/**
 * Created by PhpStorm.
 * User: Black
 * Date: 29.10.2015
 * Time: 8:11
 */
abstract class BaseDigiPass
{
    const DP_OPTIONS = 'DP_OPTIONS';
    const DP_OPTION_PREFIX = 'dp_netlicensing_';

    /**
     * Returns default options.
     * If you override the options here, be careful to use escape characters!
     */
    protected function _dp_get_default_options()
    {
        $default_options = array(
            self::DP_OPTION_PREFIX . 'apikey' => '',
            'option2' => '0',
            self::DP_OPTION_PREFIX . 'username' => '',
            self::DP_OPTION_PREFIX . 'password' => '',
        );

        return $default_options;
    }

    /**
     * Retrieves (and sanitises) options
     */
    protected function _dp_get_options()
    {
        $options = $this->_dp_get_default_options();
        $stored_options = get_option(self::DP_OPTIONS);
        if (!empty($stored_options)) {
            $this->dp_sanitize_fields($stored_options);
            $options = wp_parse_args($stored_options, $options);
        }
        update_option(self::DP_OPTIONS, $options);
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
        update_option(self::DP_OPTIONS, $options);
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function dp_sanitize_fields($input)
    {
        if (isset($input[self::DP_OPTION_PREFIX . 'apikey'])) {
            $input[self::DP_OPTION_PREFIX . 'apikey'] = sanitize_text_field($input[self::DP_OPTION_PREFIX . 'apikey']);
        }

        return $input;
    }
} 