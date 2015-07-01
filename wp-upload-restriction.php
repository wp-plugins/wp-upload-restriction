<?php
define('WP_UPLOAD_RESTRICTION_DB_VER', 1002);
/*
  Plugin Name: WP Upload Restriction
  Plugin URI: https://wordpress.org/plugins/wp-upload-restriction/
  Description: This plugin allow users to upload files of selected types.
  Version: 2.0.0
  Author: Sajjad Hossain
  Author URI: http://www.sajjadhossain.com
 */

class WPUploadRestriction {
    private $plugin_name;
    private $plugin_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_path = basename(dirname(__FILE__));
        $this->addActions();
        $this->addFilters();
    }

    /**
     * Adds actions
     */
    private function addActions() {
        add_action('init', array($this, 'updateDB'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueJS'));
        add_action('wp_ajax_get_selected_mimes_by_role', array($this, 'getSelectedMimeTypesByRole'));
        add_action('wp_ajax_save_selected_mimes_by_role', array($this, 'saveSelectedMimeTypesByRole'));
    }

    /**
     * Adds filters
     */
    private function addFilters() {
        add_filter('plugin_action_links', array($this, 'addSettingsLink'), 10, 2);
        add_filter('upload_mimes', array($this, 'restictMimes'), 10, 1);
    }

    /**
     * Loads text domain
     */
    public function init() {
        load_plugin_textdomain('wp_upload_restriction', false,  $this->plugin_path . '/languages');
        wp_register_style('wp-upload-restrictions-styles', plugins_url('css/wp-upload-restrictions-styles.css', __FILE__));
    }
    
    /**
     * Enqueue JS file
     * 
     * @param type $hook
     */
    public function enqueueJS($hook){
        if( 'wp-upload-restriction/settings.php' == $hook ) {
            wp_enqueue_script( 'wp-upload-restriction-js', plugins_url('js/wp-upload-restriction.js', __FILE__), array('jquery') );            
        } 
    }

    /**
     * Filters allowed MIME types array.
     * 
     * @global object $user
     * @param array $mimes
     * @return array
     */
    function restictMimes($mimes) {
        $user = wp_get_current_user();
        $user_roles = $user->roles;

        $selected_mimes = array();
		$has_setup = TRUE;
        
        foreach ($user_roles as $role){
            $roles_selected_mimes = get_option('wpur_selected_mimes_' . $role, FALSE);

            if($roles_selected_mimes !== FALSE){
                $selected_mimes = array_merge($selected_mimes, $roles_selected_mimes);
				$has_setup = TRUE;
            }
			elseif(!$$has_setup){
				$has_setup = FALSE;
			}
        }
		
		if(!$has_setup){
			return $mimes;
		}

        if (empty($selected_mimes)) {
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
    public function addAdminMenu() {
        add_submenu_page('options-general.php', 'WP Upload Restriction', 'WP Upload Restriction', 'manage_options', 'wp-upload-restriction/settings.php');
    }

    /**
     * Add settings link in Plugins page.
     * 
     * @param array $links
     * @param string $file
     * @return array
     */
    public function addSettingsLink($links, $file) {

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
        global $wp_roles;
        
        delete_option('wpur_selected_mimes');
        delete_site_option('wpur_db_version');
        
        foreach($wp_roles->roles as $role => $details){
            delete_option('wpur_selected_mimes_' . $role);
        }
    }

    /**
     * Process settings form post.
     * 
     * @return boolean
     */
    public function saveSelectedMimeTypesByRole() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' 
                && isset($_POST['wpur_nonce']) && wp_verify_nonce($_POST['wpur_nonce'], 'wp-upload-restrict')
                && !empty($_POST['role'])
                && in_array($_POST['role'], $this->getAllRolesArray())) {
            if (!empty($_POST['types'])) {
                $types = array();
                foreach ($_POST['types'] as $type_str) {
                    list($ext, $mime) = explode('::', $type_str);
                    $types[$ext] = $mime;
                }

                update_option('wpur_selected_mimes_' . $_POST['role'], $types);
                echo 'yes';
            }
            else {
                update_option('wpur_selected_mimes_' . $_POST['role'], array());
                echo 'yes';
            }
            wp_die();
        }

        echo 'no';
        wp_die();
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
        ksort($wp_mime_types);
        return $wp_mime_types;
    }

    /**
     * Returns user selected MIME types.
     * 
     * @return array
     */
    public function getSelectedMimeTypes($role) {
        return get_option('wpur_selected_mimes_' . $role, FALSE);
    }
    
    /**
     * Shows role wise selected MIME types
     */
    public function getSelectedMimeTypesByRole(){
        if(!empty($_POST['role']) && in_array($_POST['role'], $this->getAllRolesArray())){
            $wp_mime_types = $this->getWPSupportedMimeTypes();
            $selected_mimes = $this->getSelectedMimeTypes($_POST['role']);
    
            $check_all = $selected_mimes === FALSE;
            $output = '';
            $i = 1;
            
            foreach($wp_mime_types as $ext => $type){
                $checked = $check_all ? 'checked="checked"' : (isset($selected_mimes[$ext]) ? 'checked="checked"' : '');
                $output .= '<div>
                    <label for="ext_' . $i . '">
                    <input id="ext_' . $i . '" type="checkbox" name="types[]" ' . $checked . ' value="'. $ext . '::' . $type . '"> ' . $this->processExtention($ext) . '
                    </label>
                </div>';
        
                $i++;
            }
            
            echo $output;
            wp_die();
        }
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

    /**
     * Returns all roles
     * 
     * @global type $wp_roles
     * @return type
     */
    public function getAllRoles(){
        global $wp_roles;

        return $wp_roles->roles;
    }
    
    /**
     * Returns an array of all roles machine names
     * 
     * @global type $wp_roles
     * @return type
     */
    private function getAllRolesArray(){
        global $wp_roles;
        
        $roles = array();
        
        foreach($wp_roles->roles as $role => $details){
            $roles[] = $role;
        }
        
        return $roles;
    }
    
    /**
     * For updating database on version upgrade
     */
    public function updateDB(){
        $current_db_ver = get_site_option('wpur_db_version', 1001);

        if($current_db_ver < WP_UPLOAD_RESTRICTION_DB_VER){
            for($i = ($current_db_ver + 1); $i <= WP_UPLOAD_RESTRICTION_DB_VER; $i++){
                $function_name = 'updateDB' . $i;
                $this->$function_name();
                update_site_option('wpur_db_version', $i);
            }
        }
    }
    
    /**
     * DB update 1002
     */
    private function updateDB1002(){
        $roles = $this->getAllRoles();
        $selected_mimes = get_option('wpur_selected_mimes', FALSE);
        $all_mimes = $this->getWPSupportedMimeTypes();
        
        foreach($roles as $role => $details){
            if($role == 'administrator' || $selected_mimes === FALSE){       
                update_option('wpur_selected_mimes_' . $role, $all_mimes);
            }
            else{
                update_option('wpur_selected_mimes_' . $role, $selected_mimes);
            }
        }
        
        delete_option('wpur_selected_mimes');
    }
}

$wpUploadRestriction = new WPUploadRestriction();

register_uninstall_hook(__FILE__, array('WPUploadRestriction', 'uninstall'));
//register_deactivation_hook( __FILE__, array('WPUploadRestriction', 'uninstall'));