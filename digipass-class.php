<?php
/**
 * Plugin class.
 *
 * @package   DigiPass
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2014 Labs64
 */

class DigiPass
{

    /**
     * Instance of this class.
     *
     * @var      object
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

        // Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
        add_filter('attachment_fields_to_edit', array($this, 'get_attachment_fields'), null, 2);
        add_filter('attachment_fields_to_save', array($this, 'save_attachment_fields'), null, 2);

        add_filter('manage_media_columns', array($this, 'digipass_attachment_columns'), null, 2);
        add_action('manage_media_custom_column', array($this, 'digipass_attachment_show_column'), null, 2);

        add_action('admin_footer', array($this, 'get_media_data_javascript'));
        add_action('admin_footer', array($this, 'get_media_data_style'));
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
     * @param    boolean $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
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
                }
                restore_current_blog();
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
     * @param    boolean $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
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
                }
                restore_current_blog();
            } else {
                self::single_deactivate();
            }
        } else {
            self::single_deactivate();
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
        // TODO: Define activation functionality here
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     */
    private static function single_deactivate()
    {
        // TODO: Define deactivation functionality here
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain()
    {
        $domain = DP_SLUG;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Register and enqueue public-facing style sheet.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(DP_SLUG . '-plugin-styles', plugins_url('css/dp-public.css', __FILE__), array(), DP_VERSION);
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(DP_SLUG . '-plugin-script', plugins_url('js/dp-public.js', __FILE__), array('jquery'), DP_VERSION);
    }

    public function get_attachment_fields($form_fields, $post)
    {
        $selected_source = get_post_meta($post->ID, "digipass-source", true);
        $ct_retriever_enabled = get_single_option('ct_feature_retriever');

        $form_fields["digipass-ident_nr"] = array(
            "label" => __('Ident-Nr.', DP_SLUG),
            "input" => "text",
            "value" => get_post_meta($post->ID, "digipass-ident_nr", true),
            "helps" => __("The original object number at the source", DP_SLUG),
        );

        if ($ct_retriever_enabled == '1') {
            $btn_state = '';
            $link_activate = "";
        } else {
            $btn_state = 'disabled';
            $link_activate = "<a href='" . admin_url('options-general.php?page=digipass') . "'>" . __("activate", DP_SLUG) . "</a>";
        }

        $form_fields["digipass-source"] = array(
            "label" => __('Source', DP_SLUG),
            "input" => "html",
            "value" => $selected_source,
            "html" => "<select name='attachments[$post->ID][digipass-source]' id='attachments-{$post->ID}-digipass-source'>" . get_combobox_options(ct_get_sources_names_array(), $selected_source) . "</select>&nbsp;&nbsp;<button id='mediadata' type='button' " . $btn_state . ">" . __("GET MEDIA DATA", DP_SLUG) . "</button>" . "&nbsp;" . $link_activate,
            "helps" => __("Source where to locate the original media", DP_SLUG),
        );

        $form_fields["digipass-author"] = array(
            "label" => __('Author', DP_SLUG),
            "input" => "text",
            "value" => get_post_meta($post->ID, "digipass-author", true),
            "helps" => __("Media author/owner", DP_SLUG),
        );

        $form_fields["digipass-publisher"] = array(
            "label" => __('Publisher', DP_SLUG),
            "input" => "text",
            "value" => get_post_meta($post->ID, "digipass-publisher", true),
            "helps" => __("Media publisher (e.g. image agency)", DP_SLUG),
        );

        $form_fields["digipass-license"] = array(
            "label" => __('License', DP_SLUG),
            "input" => "text",
            "value" => get_post_meta($post->ID, "digipass-license", true),
            "helps" => __("Media license", DP_SLUG),
        );

        $form_fields["digipass-link"] = array(
            "label" => __('Link', DP_SLUG),
            "input" => "text",
            "value" => get_post_meta($post->ID, "digipass-link", true),
            "helps" => __("Media link", DP_SLUG),
        );

        return $form_fields;
    }

    public function save_attachment_fields($post, $attachment)
    {
        if (isset($attachment['digipass-ident_nr'])) {
            update_post_meta($post['ID'], 'digipass-ident_nr', $attachment['digipass-ident_nr']);
        } else {
            delete_post_meta($post['ID'], 'digipass-ident_nr');
        }

        if (isset($attachment['digipass-source'])) {
            update_post_meta($post['ID'], 'digipass-source', $attachment['digipass-source']);
        } else {
            delete_post_meta($post['ID'], 'digipass-source');
        }

        if (isset($attachment['digipass-author'])) {
            update_post_meta($post['ID'], 'digipass-author', $attachment['digipass-author']);
        } else {
            delete_post_meta($post['ID'], 'digipass-author');
        }

        if (isset($attachment['digipass-publisher'])) {
            update_post_meta($post['ID'], 'digipass-publisher', $attachment['digipass-publisher']);
        } else {
            delete_post_meta($post['ID'], 'digipass-publisher');
        }

        if (isset($attachment['digipass-license'])) {
            update_post_meta($post['ID'], 'digipass-license', $attachment['digipass-license']);
        } else {
            delete_post_meta($post['ID'], 'digipass-license');
        }

        if (isset($attachment['digipass-link'])) {
            update_post_meta($post['ID'], 'digipass-link', $attachment['digipass-link']);
        } else {
            delete_post_meta($post['ID'], 'digipass-link');
        }

        return $post;
    }

    function digipass_attachment_columns($columns)
    {
        $columns['digipass-ident_nr'] = __('Ident-Nr.', DP_SLUG);
        $columns['digipass-source'] = __('Source', DP_SLUG);
        $columns['digipass-author'] = __('Author', DP_SLUG);
        return $columns;
    }

    function digipass_attachment_show_column($name)
    {
        global $post;
        switch ($name) {
            case 'digipass-ident_nr':
                $value = get_post_meta($post->ID, "digipass-ident_nr", true);
                echo $value;
                break;
            case 'digipass-source':
                $value = get_post_meta($post->ID, "digipass-source", true);
                echo ct_get_source_caption($value);
                break;
            case 'digipass-author':
                $value = get_post_meta($post->ID, "digipass-author", true);
                echo $value;
                break;
        }
    }

    function get_media_data_javascript()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $("#mediadata").click(function () {
                    var data = {
                        action: 'get_media_data',
                        source: $("[id$=digipass-source]").val(),
                        ident_nr: $("[id$=digipass-ident_nr]").val()
                    };

                    $.post(ajaxurl, data, function (response) {
                        // alert('Got this from the server: ' + response);
                        var mediadata = jQuery.parseJSON(response);
                        $("[id$=digipass-author]").val(mediadata.author);
                        $("[id$=digipass-publisher]").val(mediadata.publisher);
                        $("[id$=digipass-license]").val(mediadata.license);
                        $("[id$=digipass-link]").val(mediadata.link);
                    });
                });
            });
        </script>
    <?php
    }

    function get_media_data_style()
    {
        ?>
        <style type="text/css">
        </style>
    <?php
    }

}
