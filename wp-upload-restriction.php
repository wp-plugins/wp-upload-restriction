<?php

/*
  Plugin Name: WP Upload Restriction
  Plugin URI: https://wordpress.org/plugins/wp-upload-restriction/
  Description: This plugin allow users to upload files of selected types.
  Version: 1.0.0
  Author: msh134
  Author URI: http://www.sajjadhossain.com
 */

class WPUploadRestriction {

    private $plugin_name;

    /**
     * Constructor
     */
    public function __construct() {
        $this->add_actions();
        $this->add_filters();
    }

    /**
     * Adds actions
     */
    private function add_actions() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Adds filters
     */
    private function add_filters() {
        add_filter('plugin_action_links', array($this, 'add_settings_link'), 10, 2);
        add_filter('upload_mimes', array($this, 'restict_mime'), 10, 1);
    }

    /**
     * Loads text domain
     */
    public function init() {
        load_plugin_textdomain('wp_upload_restriction', false, basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Filters allowed MIME types array.
     * 
     * @global object $user
     * @param array $mimes
     * @return array
     */
    function restict_mime($mimes) {
        $user = wp_get_current_user();

        if ($this->hasRole($user, 'administrator')) {
            return $mimes;
        }

        $selected_mimes = get_option('wpur_selected_mimes', FALSE);

        if ($selected_mimes === FALSE) {
            return $mimes;
        }
        elseif (empty($selected_mimes)) {
            return $selected_mimes;
        }

        if (function_exists('current_user_can')) {
            $unfiltered = $user ? user_can($user, 'unfiltered_html') : current_user_can('unfiltered_html');
        }

        if (empty($unfiltered)) {
            unset($selected_mimes['htm|html']);
        }

        return $selected_mimes;
    }

    /**
     * Add a submenu for settings page under Settings menu
     */
    public function add_admin_menu() {
        add_submenu_page('options-general.php', 'WP Upload Restriction', 'WP Upload Restriction', 'manage_options', 'wp-upload-restriction/settings.php');
    }

    /**
     * Add settings link in Plugins page.
     * 
     * @param array $links
     * @param string $file
     * @return array
     */
    public function add_settings_link($links, $file) {

        if (is_null($this->plugin_name)) {
            $this->plugin_name = plugin_basename(__FILE__);
        }

        if ($file == $this->plugin_name) {
            $settings_link = '<a href="options-general.php?page=wp-upload-restriction/settings.php">' . __('Settings', 'wp_upload_restriction') . '</a>';
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    /**
     * Deletes selected MIMEs option
     */
    public function uninstall() {
        delete_option('wpur_selected_mimes');
    }

    /**
     * Process settings form post.
     * 
     * @return boolean
     */
    public function processPost() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wpur_nonce']) && wp_verify_nonce($_POST['wpur_nonce'], 'wp-upload-restrict')) {
            if (!empty($_POST['types'])) {
                $types = array();
                foreach ($_POST['types'] as $type_str) {
                    list($ext, $mime) = explode('::', $type_str);
                    $types[$ext] = $mime;
                }

                update_option('wpur_selected_mimes', $types);
                return TRUE;
            }
            else {
                update_option('wpur_selected_mimes', array());
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Modifies extention for display.
     * 
     * @param string $ext
     * @return string
     */
    public function processExtention($ext) {
        if (strpos($ext, '|')) {
            $pieces = explode('|', $ext);
            $ext = implode(', ', $pieces);
        }

        return $ext;
    }

    /**
     * Returns WordPress supported MIME types. 
     * 
     * @return array
     */
    public function getWPSupportedMimeTypes() {
        $wp_mime_types = wp_get_mime_types();
        unset($wp_mime_types['swf'], $wp_mime_types['exe']);
        return $wp_mime_types;
    }

    /**
     * Returns user selected MIME types.
     * 
     * @return array
     */
    public function getSelectedMimeTypes() {
        return get_option('wpur_selected_mimes', FALSE);
    }

    /**
     * Checks if given role exists or not in user's roles list
     * 
     * @param object $user
     * @param string $role
     * @return boolean
     */
    private function hasRole($user, $role) {
        if (!empty($user)) {
            return in_array($role, $user->roles);
        }

        return FALSE;
    }

}

$wpUploadRestriction = new WPUploadRestriction();

register_uninstall_hook(__FILE__, array('WPUploadRestriction', 'uninstall'));
