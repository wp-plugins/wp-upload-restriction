<?php
    
    if($wpUploadRestriction->processPost()){
        print '<div id="message" class="updated fade"><p>'. __('Settings saved.', 'wp_upload_restriction') . '</p></div>';
    }  
    
    $wp_mime_types = $wpUploadRestriction->getWPSupportedMimeTypes();
    $selected_mimes = $wpUploadRestriction->getSelectedMimeTypes();
    
    $check_all = $selected_mimes === FALSE;
?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2>WP Upload Restriction</h2>
    <br>
    <h4><?php  _e('Select the extensions for allowing file upload', 'wp_upload_restriction'); ?></h4>
    <p><?php  _e('Files with selected types will be allowed for uploading. This restriction will not be applied for Administrators. Hence they will have default file upload access.', 'wp_upload_restriction'); ?></p>
    
    <form action="" method="post">
        <table class="extentions form-table">
            <tbody>
                <tr>
                    <td><a class="check" href="#"><?php  _e('Check all', 'wp_upload_restriction'); ?></a> | <a class="uncheck" href="#"><?php  _e('Uncheck all', 'wp_upload_restriction'); ?></a></td>
                </tr>
                <tr>
                    <td>
                    <?php 
                        $i = 1;
                        foreach($wp_mime_types as $ext => $type):
                            
                            $checked = $check_all ? 'checked="checked"' : (isset($selected_mimes[$ext]) ? 'checked="checked"' : '');
                    ?>
                        <div>
                            <label for="ext_<?php echo $i; ?>">
                            <input id="ext_<?php echo $i; ?>" type="checkbox" name="types[]" <?php echo $checked; ?> value="<?php echo $ext . '::' . $type; ?>"> <?php echo $wpUploadRestriction->processExtention($ext); ?>
                            </label>
                        </div>
                    <?php
                        $i++;
                        endforeach;
                    ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php wp_nonce_field( 'wp-upload-restrict', 'wpur_nonce' ) ?>
        <p class="submit"><input type="submit" value="<?php  _e('Save Changes', 'wp_upload_restriction'); ?>"></p>
    </form>
</div>

<script type="text/javascript">
    (function($){
        $('table.extentions').on('click', 'a.check', function(e){
            e.preventDefault();
            $('table.extentions input[type="checkbox"]').attr('checked', 'checked');
        });
        
        $('table.extentions').on('click', 'a.uncheck', function(e){
            e.preventDefault();
            $('table.extentions input[type="checkbox"]').removeAttr('checked');
        });
        
        $('table.extentions div').css('float', 'left').css('width', '270px');
    })(jQuery);
</script>