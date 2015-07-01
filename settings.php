<?php
    wp_enqueue_style('wp-upload-restrictions-styles');
   
    $roles = $wpUploadRestriction->getAllRoles();
?>
<div id="message" class="updated fade"><p><?php _e('Settings saved.', 'wp_upload_restriction') ?></p></div>
<div id="error_message" class="error fade"><p><?php _e('Settings could not be saved.', 'wp_upload_restriction') ?></p></div>
<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2>WP Upload Restriction</h2>
    <br>
    <h4><?php _e('Select the extensions for allowing file upload', 'wp_upload_restriction'); ?></h4>
    <p><?php _e('Files with selected types will be allowed for uploading. This restriction will not be applied for Administrators. Hence they will have default file upload access.', 'wp_upload_restriction'); ?></p>
    
    <div class="role-list">
        <div class="sub-title">Roles</div>
        <div class="wp-roles">
        <?php foreach($roles as $key => $role):?>
        <a href="<?php print $key; ?>"><?php print $role['name']; ?></a>
        <?php endforeach; ?>
        </div>
    </div>
    
    <div class="mime-list-section">
        <form action="" method="post" id="wp-upload-restriction-form">
            <div class="check-uncheck-links"><a class="check" href="#"><?php  _e('Check all', 'wp_upload_restriction'); ?></a> | <a class="uncheck" href="#"><?php  _e('Uncheck all', 'wp_upload_restriction'); ?></a></div>
            <h2 id="role-name">Role: <span></span></h2>
            <div id="mime-list">
 
            </div>
            <input type="hidden" name="role" value="" id="current-role">
            <input type="hidden" name="action" value="save_selected_mimes_by_role">
            <?php wp_nonce_field( 'wp-upload-restrict', 'wpur_nonce' ) ?>
            <p class="submit"></span><input type="button" value="<?php  _e('Save Changes', 'wp_upload_restriction'); ?>"> <span class="submit-loading ajax-loading-img"></p>
        </form>
    </div>
</div>
