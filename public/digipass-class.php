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
     * Plugin version, used for cache-busting of style and script file references.
     */
    const VERSION = '0.1.0';

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

        //Validate pages and posts
        add_action('template_redirect', array($this, 'check_page_nlic_connection'), 1000);

        //add licensee numbers to users list
        add_filter('manage_users_columns', array($this, 'user_column_licensee_number'));
        add_filter('manage_users_custom_column', array($this, 'user_column_licensee_number_row'), 10, 3);

        //cron
        add_action('digipass_cron', array($this, 'cron'));
    }

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
                                      ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      post_ID BIGINT(20) UNSIGNED NOT NULL,
                                      product_number TEXT NOT NULL ,
                                      product_module_number TEXT NOT NULL,
                                      PRIMARY KEY (ID),
                                      INDEX post_ID (post_ID)
                                    ) ENGINE=INNODB;";


            $wpdb->query($connection_table_sql);
        }


        if ($wpdb->get_var('SHOW TABLES LIKE "' . DIGIPASS_TABLE_VALIDATIONS . '"') != DIGIPASS_TABLE_VALIDATIONS) {
            $validations_table_sql = "CREATE TABLE " . DIGIPASS_TABLE_VALIDATIONS . "(
                                      ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      c_ID BIGINT(20) UNSIGNED NOT NULL,
                                      u_ID BIGINT(20) UNSIGNED NOT NULL,
                                      licensee_number VARCHAR(255) NOT NULL DEFAULT '',
                                      ttl INT(11) NOT NULL DEFAULT 0,
                                      PRIMARY KEY (ID),
                                      INDEX licensee_number (licensee_number),
                                      INDEX c_ID (c_ID),
                                      INDEX ttl (ttl),
                                      INDEX licensee_and_connection (licensee_number, c_ID)
                                    )ENGINE=INNODB;";
            $wpdb->query($validations_table_sql);
        }


        if ($wpdb->get_var('SHOW TABLES LIKE "' . DIGIPASS_TABLE_TOKENS . '"') != DIGIPASS_TABLE_TOKENS) {
            $tokens_table_sql = "CREATE TABLE " . DIGIPASS_TABLE_TOKENS . "(
                                 ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                 number VARCHAR(255) NOT NULL DEFAULT '',
                                 u_ID BIGINT(20) UNSIGNED NOT NULL,
                                 licensee_number VARCHAR(255) NOT NULL DEFAULT '',
                                 expiration INT(11) NOT NULL DEFAULT 0,
                                 shop_url VARCHAR(255) DEFAULT '',
                                 PRIMARY KEY (ID),
                                 INDEX licensee_number (licensee_number)
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

    public function check_page_nlic_connection()
    {
        global $post;
        global $wpdb;
        global $current_user;

        //check post type
        if ($post->post_type == 'page') {

            $admin_email = get_option('admin_email');
            $username = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'username');
            $password = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'password');

            //check if exist nl connection with page
            $connection_with_nl = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_CONNECTIONS . " WHERE post_ID = %d  LIMIT 0, 1;", $post->ID));

            if (!empty($connection_with_nl)) {
                if (!is_user_logged_in()) {
                    $this->include_template('digipass-error', array(
                        'code' => 'not_authorized',
                        'title' => __('Access denied'),
                        'message' => __('You are not authorized to access this page.')
                    ));
                    exit;
                } else {

                    //check if user don't have administrators rights
                    if (!in_array('administrator', $current_user->roles)) {

                        //get user hash
                        $licensee_number = hash('sha1', DIGIPASS_SALT . $current_user->user_login);

                        //check db validation
                        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE c_ID = %d AND licensee_number = %s LIMIT 0, 1;", $connection_with_nl->ID, $licensee_number));

                        $validate_state = FALSE;

                        if ($record) {
                            if ($record->ttl > time()) {
                                $validate_state = TRUE;
                            } else {
                                $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE post_ID = %d  AND licensee_number = %s;", $post->ID, $licensee_number));
                            }
                        }

                        if (!$validate_state) {

                            try {
                                //set connection params
                                $nlic_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NLIC_BASE_URL);
                                $nlic_connect->setSecurityCode(\NetLicensing\NetLicensingAPI::BASIC_AUTHENTICATION);
                                $nlic_connect->setUserName($username);
                                $nlic_connect->setPassword($password);

                                $licensee_service = new \NetLicensing\LicenseeService($nlic_connect);
                                $validation = $licensee_service->validate($licensee_number, $connection_with_nl->product_number, $current_user->user_login);

                                if ($validation) {
                                    foreach ($validation as $data) {
                                        if ($data['productModuleNumber'] == $connection_with_nl->product_module_number && $data['valid']) {
                                            $last_response = $nlic_connect->getLastResponse();
                                            $xml = simplexml_load_string($last_response->body);
                                            $ttl = (string)$xml['ttl'];
                                            //save to db
                                            $wpdb->insert(DIGIPASS_TABLE_VALIDATIONS, array(
                                                'c_ID' => $connection_with_nl->ID,
                                                'u_ID' => $current_user->ID,
                                                'licensee_number' => $licensee_number,
                                                'ttl' => strtotime($ttl)
                                            ));
                                            $validate_state = TRUE;
                                        }
                                    }
                                }

                                if (!$validate_state) {
                                    $shop_url = '';

                                    //check db token
                                    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . DIGIPASS_TABLE_TOKENS . " WHERE  licensee_number = %s LIMIT 0, 1;", $licensee_number));

                                    if ($record) {
                                        if ($record->expiration > time()) {
                                            $shop_url = $record->shop_url;
                                        } else {
                                            $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_TOKENS . " WHERE licensee_number = %s ;", $licensee_number));
                                        }

                                    }

                                    if (empty($shop_url)) {
                                        //create new token
                                        $token_service = new \NetLicensing\TokenService($nlic_connect);
                                        $token = $token_service->create('SHOP', $licensee_number);

                                        //save to db
                                        $wpdb->insert(DIGIPASS_TABLE_TOKENS, array(
                                            'number' => $token->getNumber(),
                                            'u_ID' => $current_user->ID,
                                            'licensee_number' => $token->getLicenseeNumber(),
                                            'expiration' => strtotime($token->getExpirationTime()),
                                            'shop_url' => $token->getShopUrl()
                                        ));

                                        $shop_url = $token->getShopUrl();
                                    }

                                    if (empty($shop_url)) {
                                        throw new \NetLicensing\NetLicensingException('Shop url empty');
                                    }

                                    $this->include_template('digipass-shop', array(
                                        'title' => __('Access denied'),
                                        'shop_url' => $shop_url,
                                        'message' => __('You do not have access to the contents of this page. To access, go to the <a href="' . $shop_url . '">Store</a> and purchase a license.', $this->plugin_slug)
                                    ));
                                    exit;
                                }


                            } catch (\NetLicensing\NetLicensingException $e) {

                                $this->include_template('digipass-error', array(
                                    'code' => $e->getCode(),
                                    'error' => $e->getMessage(),
                                    'title' => __('Access denied'),
                                    'message' => __('Error in the work with the license server, contact your site administrator.'),
                                ));

                                //send error to site administrator
                                $message = array();
                                $message[] = __('Type: ') . get_class($e) . ' [' . $e->getCode() . ']';
                                $message[] = __('Date: ') . date('Y/m/d H:i:s');
                                $message[] = __('User: ') . $current_user->user_login . '(' . $current_user->display_name . ')';
                                $message[] = __('Location: ') . '<a href="' . get_permalink($post->ID) . '">' . get_permalink($post->ID) . '</a>';
                                $message[] = __('Message: ') . $e->getMessage();
                                $message[] = __('File: ') . $e->getFile();
                                $message[] = __('Line: ') . $e->getLine();
                                $message[] = __('Importance: ') . __('Error');

                                $headers = array('Content-Type: text/html; charset=UTF-8');

                                wp_mail($admin_email, 'NetLicensing Error', implode("\n", $message), $headers);
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }

    public function include_template($template_name, $variables = array())
    {
        $active_template_dir = get_template_directory();
        if (file_exists($active_template_dir . '/' . $template_name . '.php')) {
            extract($variables);
            include($active_template_dir . '/' . $template_name . '.php');
        } else {
            extract($variables);
            include(DIGIPASS_DIR . '/templates/' . $template_name . '.php');
        }
    }

    public function user_column_licensee_number($column)
    {
        $column['licensee_number'] = __('Licensee number');
        return $column;
    }

    public function user_column_licensee_number_row($val, $column_name, $user_id)
    {
        $user = get_userdata($user_id);
        switch ($column_name) {
            case 'licensee_number' :
                //get user hash
                $licensee_number = hash('sha1', DIGIPASS_SALT . $user->user_login);
                return $licensee_number;
                break;
            default:
        }
    }

    public function cron()
    {
        global $wpdb;

        //delete validations
        $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_VALIDATIONS . " WHERE ttl < %d;", time()));

        //delete tokens
        $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_TOKENS . " WHERE expiration < %d;", time()));
    }

}
