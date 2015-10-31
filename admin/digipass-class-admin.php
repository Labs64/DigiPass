<?php

/**
 * DigiPass admin area.
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2014 Labs64
 */
class DigiPass_Admin extends BaseDigiPass
{
    /**
     * Instance of this class.
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     */
    protected $plugin_screen_hook_suffix = null;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     */
    private function __construct()
    {

        /*
         * Call $plugin_slug from public plugin class.
         */
        $plugin = DigiPass::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();

        // Load admin style sheet and JavaScript.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add the options page and menu item.
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename(plugin_dir_path(realpath(dirname(__FILE__))) . $this->plugin_slug . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));

        // Add the options page and menu item.
        add_action('admin_init', array($this, 'admin_page_init'));

        //Add meta boxes to pages content type
        add_action('add_meta_boxes', array($this, 'add_product_module_meta_box'));

        //Save product number
        add_action('save_post', array($this, 'save_product_module_meta_box_data'), 10, 2);

        //Truncate tables data if username is updated
        add_action('update_option_' . self::DP_OPTIONS, array($this, 'update_options_alter'), 10, 2);
    }

    //if this is singleton, set clone to private
    private function __clone()
    {

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
     * Register and enqueue admin-specific style sheet.
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles()
    {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/dp-admin.css', __FILE__), array(), DigiPass::VERSION);
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts()
    {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/dp-admin.js', __FILE__), array('jquery'), DigiPass::VERSION);
        }

    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu()
    {
        $this->plugin_screen_hook_suffix = add_options_page(
            __('DigiPass', $this->plugin_slug),
            __('DigiPass', $this->plugin_slug),
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_plugin_admin_page')
        );

    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_admin_page()
    {
        ?>
        <div class="wrap" xmlns="http://www.w3.org/1999/html">
            <a href="http://www.labs64.com" target="_blank" class="icon-labs64 icon32"></a>

            <h2><?php _e('DigiPass by Labs64', $this->plugin_slug); ?></h2>

            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('DP_OPTIONS_GROUP');
                $this->dp_settings_fields_hidden();
                do_settings_sections($this->plugin_slug);
                submit_button();
                ?>
            </form>
            <hr/>
            <?php
            $this->dp_print_reference_section();
            ?>
        </div>
        <div class="info_menu">
            <?php
            $this->dp_print_feedback_section();
            ?>
        </div>
    <?php
    }

    /**
     * Print sections divider
     */
    public function dp_print_divider()
    {
        ?>
        <hr/>
    <?php
    }

    /**
     * Print the Common-Section info text
     */
    public function dp_print_common_section_info()
    {
    }

    /**
     * Print the feedback section
     */
    public function dp_print_feedback_section()
    {
        ?>
        <h3><?php _e('Feedback', $this->plugin_slug); ?></h3>

        <p><?php _e('Did you find a bug? Have an idea for a plugin? Please help us improve this plugin', $this->plugin_slug); ?>
            :</p>
        <ul>
            <li>
                <a href="https://github.com/Labs64/DigiPass/issues"
                   target="_blank"><?php _e('Report a bug, or suggest an improvement', $this->plugin_slug); ?></a>
            </li>
            <li><a href="http://www.facebook.com/labs64" target="_blank"><?php _e('Like us on Facebook'); ?></a>
            </li>
            <li><a href="http://www.labs64.com/blog" target="_blank"><?php _e('Read Labs64 Blog'); ?></a></li>
        </ul>
    <?php
    }

    /**
     * Print the reference section
     */
    public function dp_print_reference_section()
    {
    }

    /**
     * Add settings action link to the plugins page.
     */
    public function add_action_links($links)
    {

        return array_merge(
            array(
                'settings' => '<a href="' . admin_url('options-general.php?page=' . $this->plugin_slug) . '">' . __('Settings', $this->plugin_slug) . '</a>'
            ),
            $links
        );

    }

    /**
     * Register and add settings
     */
    public function admin_page_init()
    {
        global $wpdb;

        register_setting(
            'DP_OPTIONS_GROUP',
            self::DP_OPTIONS,
            array($this, 'dp_sanitize_fields')
        );

        add_settings_section(
            'DP_COMMON_SETTINGS',
            __('DigiPass Settings', $this->plugin_slug),
            array($this, 'dp_print_common_section_info'),
            $this->plugin_slug
        );

        add_settings_field(
            self::DP_OPTION_PREFIX . 'apikey',
            __('NetLicensing APIKey', $this->plugin_slug),
            array($this, 'dp_text_field_callback'),
            $this->plugin_slug,
            'DP_COMMON_SETTINGS',
            array(
                'id' => self::DP_OPTION_PREFIX . 'apikey',
                'description' => __('To use the NetLicensing you need to have an APIKey. See <a href="https://www.labs64.de/confluence/x/pwCo#NetLicensingAPI(RESTful)-APIKeyIdentification" target="_blank">here</a> for more details.', $this->plugin_slug),
                'required' => TRUE
            )
        );

        add_settings_field(
            self::DP_OPTION_PREFIX . 'username',
            __('NetLicensing Username', $this->plugin_slug),
            array($this, 'dp_text_field_callback'),
            $this->plugin_slug,
            'DP_COMMON_SETTINGS',
            array(
                'id' => self::DP_OPTION_PREFIX . 'username',
                'description' => __('Enter your NetLicensing username.', $this->plugin_slug),
                'required' => TRUE
            )
        );

        add_settings_field(
            self::DP_OPTION_PREFIX . 'password',
            __('NetLicensing Password', $this->plugin_slug),
            array($this, 'dp_password_field_callback'),
            $this->plugin_slug,
            'DP_COMMON_SETTINGS',
            array(
                'id' => self::DP_OPTION_PREFIX . 'password',
                'description' => __('Enter your NetLicensing password.', $this->plugin_slug),
                'required' => TRUE
            )
        );
    }

    /**
     */
    public function dp_settings_fields_hidden()
    {
        $this->dp_print_settings_field_hidden('dp_option2');
    }

    /**
     */
    public function dp_print_settings_field_hidden($id)
    {
        $value = $this->_dp_get_single_option($id);
        echo "<input type='hidden' id='$id' name='DP_OPTIONS[$id]' value='$value' />";
    }

    /**
     */
    public function dp_text_field_callback($args)
    {
        $id = $args['id'];
        $description = $args['description'];
        $value = $this->_dp_get_single_option($id);
        $required = !empty($args['required']) ? 'required="true"' : '';
        echo '<input ' . $required . ' type="text" id="' . $id . '"' . ' name="DP_OPTIONS[' . $id . ']" value="' . $value . '" class="regular-text" />';
        echo '<p class="description">' . $description . '</p>';
    }

    /**
     */
    public function dp_checkbox_field_callback($args)
    {
        $id = $args['id'];
        $caption = $args['caption'];
        $description = $args['description'];
        $value = $this->_dp_get_single_option($id);
        $required = !empty($args['required']) ? 'required="true"' : '';
        echo '<input ' . $required . ' type="checkbox" id="' . $id . '" name="DP_OPTIONS[' . $id . ']" value="1" class="code"' . checked(1, $value, false) . '/> ' . $caption;
        echo '<p class="description">' . $description . '</p>';
    }

    /**
     */
    public function dp_password_field_callback($args)
    {
        $id = $args['id'];
        $description = $args['description'];
        $value = $this->_dp_get_single_option($id);
        $required = !empty($args['required']) ? 'required="true"' : '';
        echo '<input ' . $required . ' type="password" id="' . $id . '"' . ' name="DP_OPTIONS[' . $id . ']" value="' . $value . '" class="regular-text" />';
        echo '<p class="description">' . $description . '</p>';
    }

    /**
     * Add meta box with product module list
     */

    public function add_product_module_meta_box()
    {
        add_meta_box('dp-product-module-meta-box', __('DigiPass', $this->plugin_slug), array($this, 'product_module_meta_box'), 'page', 'side', 'low');
    }

    /**
     * Meta box content with products modules list
     */
    public function product_module_meta_box($page)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'password');

        if (empty($username) || empty($password)) {
            echo __('Set username and password on the <a href="/wp-admin/options-general.php?page=digipass">settings page</a>', $this->plugin_slug);
            return FALSE;
        }

        //check authorization
        try {
            $nl_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NL_BASE_URL);
            $nl_connect->setSecurityCode(\NetLicensing\NetLicensingAPI::BASIC_AUTHENTICATION);
            $nl_connect->setUserName($username);
            $nl_connect->setPassword($password);

            $product_modules = \NetLicensing\ProductModuleService::connect($nl_connect)->getList();
        } catch (\NetLicensing\NetLicensingException $e) {
            if ($e->getCode() == '401') {
                echo __('Authorization error. Check user name and password on the <a href="/wp-admin/options-general.php?page=digipass">settings page</a>', $this->plugin_slug);
                return FALSE;
            } else {
                echo $e->getMessage();
                return FALSE;
            }
        }
        /** @var  $product_module \NetLicensing\ProductModule */
        if (!empty($product_modules)) {
            $allowed_licensing_model = array('TryAndBuy', 'Subscription');
            foreach ($product_modules as $key => $product_module) {
                $product_module_lm = $product_module->getLicensingModel();
                if (!in_array($product_module_lm, $allowed_licensing_model) || !$product_module->getActive()) {
                    unset($product_modules[$key]);
                }
            }
        }

        if (empty($product_modules)) {
            echo __('Create at least one product module with Try & Buy or Subscription Licensing Model on the <a href="https://netlicensing.labs64.com/app/v2/content/vendor/productmodule.xhtml">Product Modules</a> page', $this->plugin_slug);
            return FALSE;
        }

        $table_name = $wpdb->prefix . 'nl_connection';
        $record = $wpdb->get_row($wpdb->prepare("SELECT product_module_number FROM " . $table_name . " WHERE post_ID = %d LIMIT 0, 1;", $page->ID));
        $db_product_module_number = (!empty($record->product_module_number)) ? $record->product_module_number : '';

        $options = '<option value="_none">' . __('None') . '</option>';

        /** @var  $product_module \NetLicensing\ProductModule */
        foreach ($product_modules as $product_module) {
            $product_module_number = $product_module->getNumber();
            $selected = ($db_product_module_number == $product_module_number) ? 'selected' : '';

            $options .= '<option ' . $selected . ' value="' . $product_module_number . '">' . $product_module_number . '</option>';
        }

        echo '<p>' . __('Product Modules') . '</p>
        <p><select name="dp_product_module">' . $options . '</select></p>';
    }

    public function update_options_alter($old_option, $new_option)
    {
        global $wpdb;

        //if username changed truncate all data
        if ($old_option[self::DP_OPTION_PREFIX . 'username'] != $new_option[self::DP_OPTION_PREFIX . 'username']) {
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'nl_connection`');
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'nl_validations`');
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'nl_tokens`');
        }
    }

    //Save meta box data when post save or updated
    public function save_product_module_meta_box_data($post_ID, $post)
    {
        global $wpdb;

        if (isset($_POST['dp_product_module'])) {

            $product_module_number = $_POST['dp_product_module'];

            if (empty($product_module_number) || $product_module_number == '_none') {
                $this->delete_product_module_meta_box_data($post_ID);
            } else {

                $table_name = $wpdb->prefix . 'nl_connection';
                $record = $wpdb->get_row($wpdb->prepare("SELECT post_ID FROM " . $table_name . " WHERE post_ID = %d  LIMIT 0, 1;", $post_ID));

                if (empty($record)) {
                    $this->create_product_module_meta_box_data($post_ID, $product_module_number);
                } else {
                    $this->update_product_module_meta_box_data($post_ID, $product_module_number);
                }
            }
        }
    }

    public function delete_product_module_meta_box_data($post_ID)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nl_connection';

        return $wpdb->query($wpdb->prepare("DELETE FROM " . $table_name . " WHERE post_ID = %d ;", $post_ID));
    }

    public function create_product_module_meta_box_data($post_ID, $product_module_number)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'password');

        $nl_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NL_BASE_URL);
        $nl_connect->setUserName($username);
        $nl_connect->setPassword($password);
        $product_module = \NetLicensing\ProductModuleService::connect($nl_connect)->get($product_module_number);

        if (empty($product_module)) {
            throw new DigiPass_AdminExtension('Failed to save a product module number.');
        }
        $table_name = $wpdb->prefix . 'nl_connection';

        return $wpdb->insert($table_name, array(
            'post_ID' => $post_ID,
            'product_number' => $product_module->getProductNumber(),
            'product_module_number' => $product_module->getNumber(),
        ), array('%d', '%s', '%s'));
    }

    public function update_product_module_meta_box_data($post_ID, $product_module_number)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DP_OPTION_PREFIX . 'password');

        $nl_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NL_BASE_URL);
        $nl_connect->setUserName($username);
        $nl_connect->setPassword($password);

        $product_module = \NetLicensing\ProductModuleService::connect($nl_connect)->get($product_module_number);

        if (empty($product_module)) {
            throw new DigiPass_AdminExtension('Failed to update the product module number.');
        }
        $table_name = $wpdb->prefix . 'nl_connection';

        return $wpdb->update($table_name, array(
            'post_ID' => $post_ID,
            'product_number' => $product_module->getProductNumber(),
            'product_module_number' => $product_module->getNumber(),
        ), array('post_ID' => $post_ID), array('%d', '%s', '%s'), array('%d'));

    }
}

class DigiPass_AdminExtension extends \NetLicensing\NetLicensingException
{

}