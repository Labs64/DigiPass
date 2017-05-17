<?php

/**
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2015 Labs64
 */
class DigiPass extends BaseDigiPass
{

    /**
     * Instance of this class.
     */
    protected static $instance = null;

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     */
    private function __construct()
    {
        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        // Load public-facing style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        //cron
        add_action('digipass_cron', array($this, 'cron'));

        //Validate pages and posts
        add_filter('the_content', array($this, 'validate_content'), -1000);
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Fired when the plugin is activated.
     *
     * @param    boolean $network_wide True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate($network_wide)
    {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_activate();

                    restore_current_blog();
                }

            } else {
                self::single_activate();
            }

        } else {
            self::single_activate();
        }

    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @param    boolean $network_wide True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate($network_wide)
    {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_deactivate();

                    restore_current_blog();

                }

            } else {
                self::single_deactivate();
            }

        } else {
            self::single_deactivate();
        }

    }

    /**
     * Fired when the plugin is uninstall.
     *
     * @param    boolean $network_wide True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function uninstall($network_wide)
    {
        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_uninstall();

                    restore_current_blog();

                }

            } else {
                self::single_uninstall();
            }

        } else {
            self::single_uninstall();
        }

    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @param    int $blog_id ID of the new blog.
     */
    public function activate_new_site($blog_id)
    {

        if (1 !== did_action('wpmu_new_blog')) {
            return;
        }

        switch_to_blog($blog_id);
        self::single_activate();
        restore_current_blog();

    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @return    array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids()
    {

        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col($sql);

    }

    /**
     * Fired for each blog when the plugin is activated.
     */
    private static function single_activate()
    {
        global $wpdb;

        if ($wpdb->get_var('SHOW TABLES LIKE "' . DIGIPASS_TABLE_CONNECTIONS . '"') != DIGIPASS_TABLE_CONNECTIONS) {
            $connection_table_sql = "CREATE TABLE " . DIGIPASS_TABLE_CONNECTIONS . "(
                                      connection_ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      post_ID BIGINT(20) UNSIGNED NOT NULL,
                                      product_number TEXT NOT NULL ,
                                      product_module_number TEXT NOT NULL,
                                      PRIMARY KEY (connection_ID),
                                      INDEX post_ID (post_ID)
                                    ) ENGINE=INNODB;";


            $wpdb->query($connection_table_sql);
        }


        if ($wpdb->get_var('SHOW TABLES LIKE "' . DIGIPASS_TABLE_VALIDATIONS . '"') != DIGIPASS_TABLE_VALIDATIONS) {
            $validations_table_sql = "CREATE TABLE " . DIGIPASS_TABLE_VALIDATIONS . "(
                                      validation_ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      connection_ID BIGINT(20) UNSIGNED NOT NULL,
                                      user_ID BIGINT(20) UNSIGNED NOT NULL,
                                      ttl INT(11) NOT NULL DEFAULT 0,
                                      PRIMARY KEY (validation_ID),
                                      INDEX user_ID (user_ID),
                                      INDEX connection_ID (connection_ID),
                                      INDEX ttl (ttl),
                                      INDEX user_and_connection (user_ID, connection_ID)
                                    ) ENGINE=INNODB;";
            $wpdb->query($validations_table_sql);
        }


        if ($wpdb->get_var('SHOW TABLES LIKE "' . DIGIPASS_TABLE_TOKENS . '"') != DIGIPASS_TABLE_TOKENS) {
            $tokens_table_sql = "CREATE TABLE " . DIGIPASS_TABLE_TOKENS . "(
                                 token_ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                 user_ID BIGINT(20) UNSIGNED NOT NULL,
                                 token_number VARCHAR(255) NOT NULL DEFAULT '',
                                 token_expiration INT(11) NOT NULL DEFAULT 0,
                                 shop_url VARCHAR(255) DEFAULT '',
                                 PRIMARY KEY (token_ID),
                                 INDEX user_ID (user_ID)
                            )ENGINE=INNODB;";
            $wpdb->query($tokens_table_sql);
        }

        wp_schedule_event(time(), 'hourly', 'digipass_cron');
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     */
    private static function single_deactivate()
    {
        wp_clear_scheduled_hook('digipass_cron');
    }

    private static function single_uninstall()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE " . DIGIPASS_TABLE_TOKENS . ";");
        $wpdb->query("DROP TABLE " . DIGIPASS_TABLE_VALIDATIONS . ";");
        $wpdb->query("DROP TABLE " . DIGIPASS_TABLE_CONNECTIONS . ";");

        delete_option(self::DIGIPASS_OPTIONS);
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain()
    {

        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');

    }

    /**
     * Register and enqueue public-facing style sheet.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('assets/css/dp-public.css', __FILE__), array(), self::VERSION);
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('assets/js/dp-public.js', __FILE__), array('jquery'), self::VERSION);
    }

    public function cron()
    {
        global $wpdb;

        //delete validations
        $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE ttl < %d;", time()));

        //delete tokens
        $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_TOKENS . " WHERE token_expiration < %d;", time()));
    }


    public function validate_content($content)
    {
        global $post;
        global $current_user;
        global $wpdb;

        //if user administrator skip all checks
        if (is_user_logged_in() && in_array('administrator', $current_user->roles)) {
            return $content;
        }

        //check if exist nl connection with page
        $nlic_connection = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_CONNECTIONS . " WHERE post_ID = %d  LIMIT 0, 1;", $post->ID));

        if (!$nlic_connection) return $content;

        //search digipass content
        if (preg_match('/<!--digipass-->/', $post->post_content, $matches)) {
            $content_array = explode($matches[0], $post->post_content, 2);
            $dp_content = $content_array[1];
        } else {
            if (preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)) {
                $content_array = explode($matches[0], $post->post_content, 2);
                $dp_content = $content_array[1];
            } else {
                $dp_content = $content;
            }
        }

        //check authorization
        if (!is_user_logged_in()) {
            $message = 'Please' . ' <a href="' . wp_login_url(get_permalink()) . '">login</a> to view this content. Not a member? - <a href="' . wp_registration_url(get_permalink()) . '">Join today</a>!';
            return str_replace($dp_content, $message, $content);
        }

        $field = self::DIGIPASS_OPTION_PREFIX . 'licensee_number';
        $licensee_number = get_user_meta($current_user->ID, $field, TRUE);

        //if user don`t have licensee number, save default licensee number to meta
        if (!$licensee_number) {
            $default_licensee_number = self::dp_get_default_licensee_number($current_user);
            if (update_user_meta($current_user->ID, $field, $default_licensee_number)) {
                $licensee_number = $default_licensee_number;
            }
        }

        if (!$licensee_number) {
            throw new \NetLicensing\NetLicensingException('Empty Licensee Number');
        }

        //check db validation
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE connection_ID = %d AND user_ID = %d LIMIT 0, 1;", $nlic_connection->connection_ID, $current_user->ID));
        $validate_db_state = FALSE;

        if ($record) {
            if ($record->ttl > time()) {
                $validate_db_state = TRUE;
            } else {
                //delete expired validation
                $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE connection_ID = %d  AND user_ID = %d;", $nlic_connection->connection_ID, $current_user->ID));
            }
        }

        if (!$validate_db_state) {
            try {
                //get connection params
                $username = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'username');
                $password = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'password');

                //made connection object
                $nlic_connect = new \NetLicensing\NetLicensingAPI();
                $nlic_connect->setSecurityCode(\NetLicensing\NetLicensingAPI::BASIC_AUTHENTICATION);
                $nlic_connect->setUserName($username);
                $nlic_connect->setPassword($password);

                $licensee_service = new \NetLicensing\LicenseeService($nlic_connect);

                $validation = $licensee_service->validate($licensee_number, $nlic_connection->product_number, $current_user->user_login);

                if ($validation) {
                    foreach ($validation as $data) {
                        if ($data['productModuleNumber'] == $nlic_connection->product_module_number && $data['valid'] == 'true') {
                            $last_response = $nlic_connect->getLastResponse();
                            $xml = simplexml_load_string($last_response->body);
                            $ttl = (string)$xml['ttl'];

                            //save validation to db
                            $wpdb->insert(DIGIPASS_TABLE_VALIDATIONS, array(
                                'connection_ID' => $nlic_connection->connection_ID,
                                'user_ID' => $current_user->ID,
                                'ttl' => strtotime($ttl)
                            ));
                            $validate_nlic_state = TRUE;
                        }
                    }
                }

                if (empty($validate_nlic_state)) {

                    $shop_url = '';

                    //check db token
                    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_TOKENS . " WHERE  user_ID = %d LIMIT 0, 1;", $current_user->ID));

                    if ($record) {
                        if ($record->token_expiration > time()) {
                            $shop_url = $record->shop_url;
                        } else {
                            $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_TOKENS . " WHERE user_ID = %d ;", $current_user->ID));
                        }
                    }

                    if (empty($shop_url)) {
                        //create new token
                        $token_service = new \NetLicensing\TokenService($nlic_connect);

                        //set Redirect properties
                        $custom_properties = array(
                            'successURL' => get_permalink($post->ID),
                            'cancelURL' => get_permalink($post->ID)
                        );

                        $token = $token_service->create('SHOP', $licensee_number, $custom_properties);

                        //save to db
                        $wpdb->insert(DIGIPASS_TABLE_TOKENS, array(
                            'user_ID' => $current_user->ID,
                            'token_number' => $token->getNumber(),
                            'token_expiration' => strtotime($token->getExpirationTime()),
                            'shop_url' => $token->getShopUrl()
                        ));

                        $shop_url = $token->getShopUrl();
                    }

                    if (empty($shop_url)) {
                        throw new \NetLicensing\NetLicensingException('Shop URL is empty');
                    }

                    $message_template = __('This content is available for purchase. See %s.');
                    $shop_link = '<a href="' . $shop_url . '" target="_blank">' . __('subscription options', $this->plugin_slug) . '</a>';
                    $message = sprintf($message_template, $shop_link);

                    return str_replace($dp_content, $message, $content);
                }

            } catch (\NetLicensing\NetLicensingException $e) {
                print_r($e->getMessage());
                $message = __('Error contacting NetLicensing license server. Please contact your site administrator.', $this->plugin_slug);

                //send error to site administrator
                $admin_email = get_option('admin_email');

                $email_message = array();
                $email_message[] = __('Severity: ') . __('Error');
                $email_message[] = __('Type: ') . get_class($e) . ' [' . $e->getCode() . ']';
                $email_message[] = __('Date: ') . date('Y/m/d H:i:s');
                $email_message[] = __('User: ') . $current_user->user_login . '(' . $current_user->display_name . ')';
                $email_message[] = __('Location: ') . '<a href="' . get_permalink($post->ID) . '">' . get_permalink($post->ID) . '</a>';
                $email_message[] = __('Message: ') . $e->getMessage();
                $email_message[] = __('File: ') . $e->getFile();
                $email_message[] = __('Line: ') . $e->getLine();

                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($admin_email, 'NetLicensing Error', implode("\n", $email_message), $headers);

                return str_replace($dp_content, $message, $content);
            }
        }

        return $content;
    }
}
