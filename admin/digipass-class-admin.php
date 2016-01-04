<?php

/**
 * DigiPass admin area.
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2015 Labs64
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

        //Add meta boxes to pages and posts content type
        add_action('add_meta_boxes', array($this, 'add_product_module_meta_box'));

        //Save product number
        add_action('save_post', array($this, 'save_product_module_meta_box_data'), 10, 2);

        //Truncate tables data if username is updated
        add_action('update_option_' . self::DIGIPASS_OPTIONS, array($this, 'update_options_alter'), 10, 2);

        // add new digipass quicktag button
        add_filter('mce_buttons', array($this, 'digipass_register_buttons'));
        add_filter('mce_external_plugins', array($this, 'digipass_add_buttons'));

        //add user licensee number meta
        add_action('show_user_profile', array($this, 'user_licensee_number_field'));
        add_action('edit_user_profile', array($this, 'user_licensee_number_field'));
        add_action('personal_options_update', array($this, 'save_user_licensee_number_field'));
        add_action('edit_user_profile_update', array($this, 'save_user_licensee_number_field'));

        //add licensee numbers to users list
        add_filter('manage_users_columns', array($this, 'user_column_licensee_number'));
        add_filter('manage_users_custom_column', array($this, 'user_column_licensee_number_row'), 10, 3);

        // AJAX callbacks registration
        add_action('wp_ajax_validate', array($this, 'digipass_validate_callback'));
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
            wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/dp-admin.css', __FILE__), array(), self::VERSION);
        }
        add_editor_style(plugins_url('assets/css/dp-tinymce-quicktags.css', __FILE__));
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
            wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/dp-admin.js', __FILE__), array('jquery'), self::VERSION);
        }
        //add digipass quicktag
        wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/dp-quicktags.js', __FILE__), array('jquery'), self::VERSION);
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
                settings_fields('DIGIPASS_OPTIONS_GROUP');
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
            $this->dp_print_features_section();
            $this->dp_print_divider();
            $this->dp_print_feedback_section();
            ?>
        </div>
    <?php
    }

    /**
     * Print sections divider
     */
    function dp_print_divider()
    {
        ?>
        <hr/>
    <?php
    }

    /**
     * Print the Common-Section info text
     */
    function dp_print_common_section_info()
    {
        echo '<a href="https://go.netlicensing.io/app/v2/content/register.xhtml" target="_blank">Sign up</a> for your free NetLicensing vendor account, then fill in the login information in the fields below.';
        echo '<br/>Using NetLicensing <a href="https://go.netlicensing.io/app/v2/?lc=4b566c7e20&source=lmbox001" target="_blank">demo account</a>, you can try out plugin functionality right away (username: <i>demo</i> / password: <i>demo</i>).';
    }

    /**
     * Returns available plugin features
     */
    function dp_get_features_array()
    {
        $features = array(
            'digipass_feature_protect_page' => __('Protect page / post (FREE)', $this->plugin_slug)
        );
        return $features;
    }

    /**
     * Get features list.
     */
    function dp_print_features_list($features)
    {
        $ret = '<ul id="digipass_features">';
        foreach ($features as $key => $value) {
            $ret .= '<li id="' . $key . '">&nbsp;' . $value . ' - ' . $this->dp_get_on_off($this->_dp_get_single_option($key)) . '</li>';
        }
        $ret .= '</ul>';
        print $ret;
    }

    /**
     * Print the Section info text
     */
    function dp_get_on_off($opt)
    {
        if ($opt == '1') {
            return "<span class='label-on'>ON</span>";
        } else {
            return "<span class='label-off'>OFF</span>";
        }
    }

    /**
     * Validate allowed features at Labs64 Netlicensing
     */
    function digipass_validate_callback()
    {
        // validate features
        $nlic_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NLIC_BASE_URL);
        $nlic_connect->setSecurityCode(\NetLicensing\NetLicensingAPI::API_KEY_IDENTIFICATION);
        $nlic_connect->setApiKey(DIGIPASS_NLIC_API_KEY);

        $site_url = dp_strip_url(get_site_url(), 1000);

        $licensee_service = new \NetLicensing\LicenseeService($nlic_connect);
        $validation = $licensee_service->validate($site_url, 'DP', urlencode($site_url));

        $last_response = $nlic_connect->getLastResponse();
        $xml = simplexml_load_string($last_response->body);

        // NOTE: no NetLicensing response processing at the moment necessary; only product usage tracking functionality

        // update options
        $this->_dp_set_single_option('digipass_feature_protect_page', '1');

        // prepare return values
        $licenses = array(
            'netlicensing_response' => $xml,
            'digipass_feature_protect_page' => $this->_dp_get_single_option('digipass_feature_protect_page')
        );
        echo json_encode($licenses);

        die(); // this is required to return a proper result
    }

    /**
     * Print the features section
     */
    function dp_print_features_section()
    {
        $digipass_feature_protect_page = $this->_dp_get_single_option('digipass_feature_protect_page');

        ?>
        <h3><?php _e('Features', $this->plugin_slug); ?></h3>
        <p><?php _e('Available plugin features', $this->plugin_slug); ?>:</p>

        <?php $this->dp_print_features_list($this->dp_get_features_array()); ?>

        <button id="digipass-validate" type="button""><?php _e('Validate'); ?></button>
        <br/>
        <div style="font-style: italic; color: rgb(102, 102, 102); font-size: smaller;"><p>Powered by <a
                    href="http://netlicensing.io"
                    target="_blank">NetLicensing</a></p>
        </div>
    <?php
    }

    /**
     * Print the feedback section
     */
    function dp_print_feedback_section()
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
    function dp_print_reference_section()
    {
        ?>
        <h3><?php _e('Plugin Reference', $this->plugin_slug); ?></h3>
        <h4>Start your monetization in minutes</h4>
        <ul>
            <li>- <a href="https://netlicensing.labs64.com/app/v2/content/register.xhtml" target="_blank">Create</a> a
                NetLicensing account in just a few minutes
            </li>
            <li>- Configure your <a href="https://netlicensing.labs64.com/app/v2/content/vendor/product.xhtml"
                                    target="_blank">product</a> for you specific needs
            </li>
            <li>- Enter NetLicensing credentials in the <i>'NetLicensing Connect'</i> section</li>
            <li>- While creating a new post or page select licensing model in the DigiPass meta box</li>
        </ul>
    <?php
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
            'DIGIPASS_OPTIONS_GROUP',
            self::DIGIPASS_OPTIONS,
            array($this, 'dp_sanitize_fields')
        );

        add_settings_section(
            'DIGIPASS_COMMON_SETTINGS',
            __('NetLicensing Connect', $this->plugin_slug),
            array($this, 'dp_print_common_section_info'),
            $this->plugin_slug
        );

        add_settings_field(
            self::DIGIPASS_OPTION_PREFIX . 'username',
            __('Username', $this->plugin_slug),
            array($this, 'dp_text_field_callback'),
            $this->plugin_slug,
            'DIGIPASS_COMMON_SETTINGS',
            array(
                'id' => self::DIGIPASS_OPTION_PREFIX . 'username',
                'description' => __('Enter your NetLicensing username.', $this->plugin_slug),
                'required' => TRUE
            )
        );

        add_settings_field(
            self::DIGIPASS_OPTION_PREFIX . 'password',
            __('Password', $this->plugin_slug),
            array($this, 'dp_password_field_callback'),
            $this->plugin_slug,
            'DIGIPASS_COMMON_SETTINGS',
            array(
                'id' => self::DIGIPASS_OPTION_PREFIX . 'password',
                'description' => __('Enter your NetLicensing password.', $this->plugin_slug),
                'required' => TRUE
            )
        );
    }

    /**
     */
    function dp_settings_fields_hidden()
    {
        $this->dp_print_settings_field_hidden('digipass_feature_protect_page');
    }

    /**
     */
    function dp_print_settings_field_hidden($id)
    {
        $value = $this->_dp_get_single_option($id);
        echo "<input type='hidden' id='$id' name='DIGIPASS_OPTIONS[$id]' value='$value' />";
    }

    /**
     */
    public function dp_text_field_callback($args)
    {
        $id = $args['id'];
        $description = $args['description'];
        $value = $this->_dp_get_single_option($id);
        $required = !empty($args['required']) ? 'required="true"' : '';
        echo '<input ' . $required . ' type="text" id="' . $id . '"' . ' name="DIGIPASS_OPTIONS[' . $id . ']" value="' . $value . '" class="regular-text" />';
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
        echo '<input ' . $required . ' type="checkbox" id="' . $id . '" name="DIGIPASS_OPTIONS[' . $id . ']" value="1" class="code"' . checked(1, $value, false) . '/> ' . $caption;
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
        echo '<input ' . $required . ' type="password" id="' . $id . '"' . ' name="DIGIPASS_OPTIONS[' . $id . ']" value="' . $value . '" class="regular-text" />';
        echo '<p class="description">' . $description . '</p>';
    }

    /**
     * Add meta box with product module list
     */

    public function add_product_module_meta_box()
    {
        add_meta_box('dp-product-module-meta-box', __('DigiPass', $this->plugin_slug), array($this, 'product_module_meta_box'), 'page', 'side', 'low');
        add_meta_box('dp-product-module-meta-box', __('DigiPass', $this->plugin_slug), array($this, 'product_module_meta_box'), 'post', 'side', 'low');
    }

    /**
     * Meta box content with products modules list
     */
    public function product_module_meta_box($page)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'password');

        if (empty($username) || empty($password)) {

            echo __('<span style="color: red;">Authorization error</span><br/><br/>Define username and password on the DigiPass <a href="' . admin_url('options-general.php?page=digipass') . '">settings</a> page.', $this->plugin_slug);
            return FALSE;
        }

        //check authorization
        try {
            $nlic_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NLIC_BASE_URL);
            $nlic_connect->setSecurityCode(\NetLicensing\NetLicensingAPI::BASIC_AUTHENTICATION);
            $nlic_connect->setUserName($username);
            $nlic_connect->setPassword($password);

            $product_modules = \NetLicensing\ProductModuleService::connect($nlic_connect)->getList();
        } catch (\NetLicensing\NetLicensingException $e) {
            if ($e->getCode() == '401') {
                echo __('<span style="color: red;">Authorization error</span><br/><br/>Check username and password on the DigiPass <a href="' . admin_url('options-general.php?page=digipass') . '">settings</a> page.', $this->plugin_slug);
                return FALSE;
            } else {
                echo $e->getMessage();
                return FALSE;
            }
        }
        /** @var  $product_module \NetLicensing\ProductModule */
        if (!empty($product_modules)) {
            // NOTE: AAV: TryAndBuy will be enabled with the one of the next stable releases
            // $allowed_licensing_model = array('TryAndBuy', 'Subscription');
            $allowed_licensing_model = array('Subscription');
            foreach ($product_modules as $key => $product_module) {
                $product_module_lm = $product_module->getLicensingModel();
                if (!in_array($product_module_lm, $allowed_licensing_model) || !$product_module->getActive()) {
                    unset($product_modules[$key]);
                }
            }
        }

        if (empty($product_modules)) {
            // NOTE: AAV: TryAndBuy will be enabled with the one of the next stable releases
//            echo __('Create at least one product module using <i>Try & Buy</i> or <i>Subscription</i> Licensing Model at NetLicensing <a href="https://netlicensing.labs64.com/app/v2/content/vendor/productmodule.xhtml" target="_blank">Product Modules</a> page', $this->plugin_slug);
            echo __('Create at least one product module using <i>Subscription</i> Licensing Model at NetLicensing <a href="https://netlicensing.labs64.com/app/v2/content/vendor/productmodule.xhtml" target="_blank">Product Modules</a> page', $this->plugin_slug);
            return FALSE;
        }

        $record = $wpdb->get_row($wpdb->prepare("SELECT product_module_number FROM " . DIGIPASS_TABLE_CONNECTIONS . " WHERE post_ID = %d LIMIT 0, 1;", $page->ID));
        $db_product_module_number = (!empty($record->product_module_number)) ? $record->product_module_number : '';

        $options = '<option value="_none">' . __('None') . '</option>';

        /** @var  $product_module \NetLicensing\ProductModule */
        foreach ($product_modules as $product_module) {
            if ($product_module->getActive()) {
                $product_module_number = $product_module->getNumber();
                $product_module_name = $product_module->getName();
                $selected = ($db_product_module_number == $product_module_number) ? 'selected' : '';

                $options .= '<option ' . $selected . ' value="' . $product_module_number . '">' . $product_module_name . ' (' . $product_module_number . ')' . '</option>';
            }
        }

        echo '<p>' . __('Protect this page. Select licensing model:') . '</p>';
        echo '<p><select name="dp_product_module">' . $options . '</select></p>';
        echo '<p>' . __('Do not see the suitable licensing model? Configure NetLicensing <a href="https://netlicensing.labs64.com/app/v2/content/vendor/productmodule.xhtml" target="_blank">product module</a>.') . '</p>';
    }

    public function update_options_alter($old_option, $new_option)
    {
        global $wpdb;

        // if username changed truncate all data
        if ($old_option[self::DIGIPASS_OPTION_PREFIX . 'username'] != $new_option[self::DIGIPASS_OPTION_PREFIX . 'username']) {
            $wpdb->query('TRUNCATE TABLE ' . DIGIPASS_TABLE_CONNECTIONS);
            $wpdb->query('TRUNCATE TABLE ' . DIGIPASS_TABLE_VALIDATIONS);
            $wpdb->query('TRUNCATE TABLE ' . DIGIPASS_TABLE_TOKENS);
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
                $record = $wpdb->get_row($wpdb->prepare("SELECT post_ID FROM " . DIGIPASS_TABLE_CONNECTIONS . " WHERE post_ID = %d  LIMIT 0, 1;", $post_ID));

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

        return $wpdb->query($wpdb->prepare("DELETE FROM " . DIGIPASS_TABLE_CONNECTIONS . " WHERE post_ID = %d ;", $post_ID));
    }

    public function create_product_module_meta_box_data($post_ID, $product_module_number)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'password');

        $nlic_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NLIC_BASE_URL);
        $nlic_connect->setUserName($username);
        $nlic_connect->setPassword($password);
        $product_module = \NetLicensing\ProductModuleService::connect($nlic_connect)->get($product_module_number);

        if (empty($product_module)) {
            throw new DigiPass_AdminExtension('Failed to save a product module number.');
        }

        return $wpdb->insert(DIGIPASS_TABLE_CONNECTIONS, array(
            'post_ID' => $post_ID,
            'product_number' => $product_module->getProductNumber(),
            'product_module_number' => $product_module->getNumber(),
        ), array('%d', '%s', '%s'));
    }

    public function update_product_module_meta_box_data($post_ID, $product_module_number)
    {
        global $wpdb;

        $username = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'username');
        $password = $this->_dp_get_single_option(self::DIGIPASS_OPTION_PREFIX . 'password');

        $nlic_connect = new \NetLicensing\NetLicensingAPI(DIGIPASS_NLIC_BASE_URL);
        $nlic_connect->setUserName($username);
        $nlic_connect->setPassword($password);

        $product_module = \NetLicensing\ProductModuleService::connect($nlic_connect)->get($product_module_number);

        if (empty($product_module)) {
            throw new DigiPass_AdminExtension('Failed to update the product module number.');
        }

        return $wpdb->update(DIGIPASS_TABLE_CONNECTIONS, array(
            'post_ID' => $post_ID,
            'product_number' => $product_module->getProductNumber(),
            'product_module_number' => $product_module->getNumber(),
        ), array('post_ID' => $post_ID), array('%d', '%s', '%s'), array('%d'));

    }

    public function digipass_register_buttons($buttons)
    {
        array_push($buttons, 'digipass');
        return $buttons;
    }

    public function digipass_add_buttons($plugin_array)
    {
        $plugin_array['digipass'] = plugins_url('assets/js/dp-tinymce-quicktags.js', __FILE__);
        return $plugin_array;
    }

    public function user_licensee_number_field($user)
    {
        $field = self::DIGIPASS_OPTION_PREFIX . 'licensee_number';

        $value = get_user_meta($user->ID, $field, TRUE);
        $value = ($value) ? $value : self::dp_get_default_licensee_number($user);

        $form = '<h3>' . __('Licensee Number') . '</h3>
	            <table class="form-table">
                    <tr>
                        <th><label for="twitter">' . __('Licensee Number') . '</label></th>
                        <td>
                            <input type="text" name="' . $field . '" id="' . $field . '" value="' . $value . '" class="regular-text" /><br />
                            <span class="description">' . __('Please enter your Licensee Number') . '</span>
                        </td>
                    </tr>
	            </table>';

        print $form;
    }

    public function save_user_licensee_number_field($user_id)
    {
        if (!current_user_can('edit_user', $user_id))
            return false;

        $field = self::DIGIPASS_OPTION_PREFIX . 'licensee_number';
        update_user_meta($user_id, $field, $_POST[$field]);
    }

    public function user_column_licensee_number($column)
    {
        $column['licensee_number'] = __('DigiPass Licensee Number');
        return $column;
    }

    public function user_column_licensee_number_row($val, $column_name, $user_id)
    {
        $field = self::DIGIPASS_OPTION_PREFIX . 'licensee_number';
        $licensee_number = get_user_meta($user_id, $field, TRUE);

        $licensee_number = ($licensee_number) ? $licensee_number : self::dp_get_default_licensee_number(get_userdata($user_id));

        switch ($column_name) {
            case 'licensee_number' :
                return $licensee_number;
                break;
            default:
        }
    }
}

class DigiPass_AdminExtension extends \NetLicensing\NetLicensingException
{

}
