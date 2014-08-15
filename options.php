<?php
/**
 * Plugin options page.
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2014 Labs64
 */


define('DP_OPTIONS', 'DP_OPTIONS');
define('API_KEY', '31c7bc4e-90ff-44fb-9f07-b88eb06ed9dc');


if (is_admin()) {
    // Add the options page and menu item.
    add_action('admin_menu', 'add_plugin_page');
    add_action('admin_init', 'page_init');

    // Add an action link pointing to the options page.
    $plugin_basename = plugin_basename(plugin_dir_path(__FILE__) . 'digipass.php');
    add_filter('plugin_action_links_' . $plugin_basename, 'add_action_links');

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
    add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

    // Get media data callback registration
    add_action('wp_ajax_validate', 'validate_callback');
    add_action('wp_ajax_get_media_data', 'get_media_data_callback');
}

/**
 * Add settings action link to the plugins page.
 */
function add_action_links($links)
{
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url('options-general.php?page=digipass') . '">' . __('Settings', DP_SLUG) . '</a>'
        ),
        $links
    );
}

/**
 * Add options page
 */
function add_plugin_page()
{
    global $plugin_screen_hook_suffix;
    $plugin_screen_hook_suffix = add_options_page(
        __('DigiPass', DP_SLUG),
        __('DigiPass', DP_SLUG),
        'manage_options',
        DP_SLUG,
        'create_admin_page'
    );
}

/**
 * Register and enqueue admin-specific style sheet.
 *
 * @return    null    Return early if no settings page is registered.
 */
function enqueue_admin_styles()
{
    global $plugin_screen_hook_suffix;

    if (!isset($plugin_screen_hook_suffix)) {
        return;
    }

    $screen = get_current_screen();
    if ($screen->id == $plugin_screen_hook_suffix) {
        wp_enqueue_style(DP_SLUG . '-admin-styles', plugins_url('css/dp-admin.css', __FILE__), array(), DP_VERSION);
    }

}

/**
 * Register and enqueue admin-specific JavaScript.
 *
 * @return    null    Return early if no settings page is registered.
 */
function enqueue_admin_scripts()
{
    global $plugin_screen_hook_suffix;

    if (!isset($plugin_screen_hook_suffix)) {
        return;
    }

    $screen = get_current_screen();
    if ($screen->id == $plugin_screen_hook_suffix) {
        wp_enqueue_script(DP_SLUG . '-admin-script', plugins_url('js/dp-admin.js', __FILE__), array('jquery'), DP_VERSION);
    }

}

/**
 * Options page callback
 */
function create_admin_page()
{
    ?>
    <div class="wrap" xmlns="http://www.w3.org/1999/html">
        <a href="http://www.labs64.com" target="_blank" class="icon-labs64 icon32"></a>

        <h2><?php _e('DigiPass by Labs64', DP_SLUG); ?></h2>

        <form method="post" action="options.php">
            <?php
            // This prints out all hidden setting fields
            settings_fields('DP_OPTIONS_GROUP');
            settings_fields_hidden();
            do_settings_sections(DP_SLUG);
            submit_button();
            ?>
        </form>
        <hr/>
        <?php
        print_reference_section();
        ?>
    </div>
    <div class="info_menu">
        <?php
        print_feedback_section();
        ?>
    </div>
<?php
}

/**
 * Print sections divider
 */
function print_divider()
{
    ?>
    <hr/>
<?php
}

/**
 * Print the Common-Section info text
 */
function print_common_section_info()
{
}

/**
 * Print the feedback section
 */
function print_feedback_section()
{
    ?>
    <h3><?php _e('Feedback', DP_SLUG); ?></h3>

    <p><?php _e('Did you find a bug? Have an idea for a plugin? Please help us improve this plugin', DP_SLUG); ?>:</p>
    <ul>
        <li>
            <a href="https://github.com/Labs64/DigiPass/issues"
               target="_blank"><?php _e('Report a bug, or suggest an improvement', DP_SLUG); ?></a>
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
function print_reference_section()
{
}

/**
 * Register and add settings
 */
function page_init()
{
    register_setting(
        'DP_OPTIONS_GROUP', // Option group
        DP_OPTIONS, // Option name
        'sanitize' // Sanitize
    );

    add_settings_section(
        'DP_COMMON_SETTINGS', // ID
        __('DigiPass Settings', DP_SLUG), // Title
        'print_common_section_info', // Callback
        DP_SLUG // Page
    );

    add_settings_field(
        'dp_netlicensing_apikey',
        __('NetLicensing APIKey', DP_SLUG),
        'dp_text_field_callback',
        DP_SLUG,
        'DP_COMMON_SETTINGS',
        array(
            'id' => 'dp_netlicensing_apikey',
            'description' => __('To use the NetLicensing you need to have an APIKey.' . ' <a href="http://www.labs64.com/netlicensing" target="_blank">See here</a>' . ' for more details.', DP_SLUG),
        )
    );
}

/**
 * Sanitize each setting field as needed
 *
 * @param array $input Contains all settings fields as array keys
 */
function sanitize($input)
{
    $input['dp_netlicensing_apikey'] = sanitize_text_field($input['dp_netlicensing_apikey']);

    return $input;
}

/**
 */
function settings_fields_hidden()
{
    print_settings_field_hidden('dp_option2');
}

/**
 */
function print_settings_field_hidden($id)
{
    $value = get_single_option($id);
    echo "<input type='hidden' id='$id' name='DP_OPTIONS[$id]' value='$value' />";
}

/**
 */
function dp_text_field_callback($args)
{
    $id = $args['id'];
    $description = $args['description'];
    $value = get_single_option($id);
    echo "<input type='text' id='$id' name='DP_OPTIONS[$id]' value='$value' class='regular-text' />";
    echo "<p class='description'>$description</p>";
}

function dp_checkbox_field_callback($args)
{
    $id = $args['id'];
    $caption = $args['caption'];
    $description = $args['description'];
    $value = get_single_option($id);
    echo "<input type='checkbox' id='$id' name='DP_OPTIONS[$id]' value='1' class='code' " . checked(1, $value, false) . " /> $caption";
    echo "<p class='description'>$description</p>";
}

/**
 * Returns default options.
 * If you override the options here, be careful to use escape characters!
 */
function get_default_options()
{
    $default_options = array(
        'dp_netlicensing_apikey' => '',
        'dp_option2' => '0'
    );
    return $default_options;
}

/**
 * Retrieves (and sanitises) options
 */
function get_options()
{
    $options = get_default_options();
    $stored_options = get_option(DP_OPTIONS);
    if (!empty($stored_options)) {
        sanitize($stored_options);
        $options = wp_parse_args($stored_options, $options);
    }
    update_option(DP_OPTIONS, $options);
    return $options;
}

/**
 * Retrieves single option
 */
function get_single_option($name)
{
    $options = get_options();
    return $options[$name];
}

/**
 * Set single option value
 */
function set_single_option($name, $value)
{
    $options = get_options();
    $options[$name] = $value;
    update_option(DP_OPTIONS, $options);
}

?>
