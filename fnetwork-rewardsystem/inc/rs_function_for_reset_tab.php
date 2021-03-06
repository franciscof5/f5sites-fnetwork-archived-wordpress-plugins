<?php

class RSFunctionForReset {
    
    public function __construct() {
        
        add_action('woocommerce_admin_field_reset_field', array($this, 'add_admin_field_to_reward_system'));
        
        add_action('wp_ajax_nopriv_rsresetuserdata', array($this, 'process_reset_data_users'));

        add_action('wp_ajax_rsresetuserdata', array($this, 'process_reset_data_users'));
        
    }
    
    public static function add_admin_field_to_reward_system() {
        global $woocommerce;
        ?>
        <style type="text/css">
            p.submit {
                display:none;
            }
            #mainforms {
                display:none;
            }
        </style>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="rs_reset_data_for"><?php _e('Reset Data for', 'rewardsystem'); ?></label>
            </th>
            <td class="forminp forminp-select">
                <input type="radio" name="rs_reset_data_all_users" id="rs_reset_data_all_users" class="rs_reset_data_for_users" value="1" checked="checked"/>All Users<br>
                <input type="radio" name="rs_reset_data_all_users" id="rs_reset_data_selected_users" class="rs_reset_data_for_users" value="2"/>Selected Users<br>
            </td>
        </tr>
         <?php if ((float) $woocommerce->version <= (float) ('2.2.0')) { ?>                    
            <tr class="rs_reset_selected_users">
                <th class="titledesc" scope="row">
                    <label for="rs_reset_selected_user_data"> <?php _e('Select Users to Reset Data', 'rewardsystem'); ?></label>
                </th>
                <td>
                    <select name="rs_reset_selected_user_data" style="width:60%" id="rs_reset_selected_user_data" multiple="multiple" class="short rs_reset_selected_user_data">
                        <option>
                        <?php
                        $json_ids = array();
                        $getuser = get_option('rs_reset_selected_user_data');
                        if ($getuser != "") {
                            $listofuser = $getuser;
                            if (!is_array($listofuser)) {
                                $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                            } else {
                                $userids = $listofuser;
                            }

                            foreach ($userids as $userid) {
                                $user = get_user_by('id', $userid);
                                $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email);
                            }
                            echo esc_attr(json_encode($json_ids));
                        }
                        ?>    
                        </option>
                    </select>
                </td>
            </tr>
        <?php } else { ?>
            <tr valign="top" class="rs_reset_selected_users">
                <th class="titledesc" scope="row">
                    <label for="rs_reset_selected_user_data"><?php _e('Select Users to Reset Data', 'rewardsystem'); ?></label>
                </th>
                <td>
                    <input type="hidden" class="wc-customer-search" name="rs_reset_selected_user_data" id="rs_export_masterlog_users_list" data-multiple="true" data-placeholder="<?php _e('Search for a customer&hellip;', 'rewardsystem'); ?>" data-selected="<?php
                           $json_ids = array();
                           $getuser = get_option('rs_reset_selected_user_data');
                           if ($getuser != "") {
                               $listofuser = $getuser;
                               if (!is_array($listofuser)) {
                                   $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                               } else {
                                   $userids = $listofuser;
                               }

                               foreach ($userids as $userid) {
                                   $user = get_user_by('id', $userid);
                                   $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email);
                               }echo esc_attr(json_encode($json_ids));
                           }
                           ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" data-allow_clear="true" />
                </td>
            </tr>
             <?php } ?>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="rs_reset_user_reward_points">
                    <?php _e('Reset User Reward Points', 'rewardsystem'); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="rs_reset_user_reward_points" id="rs_reset_user_reward_points" value="1" checked="checked"/>
            </td>
        </tr>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="rs_reset_user_log">
                    <?php _e('Reset User Logs', 'rewardsystem'); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="rs_reset_user_log" id="rs_reset_user_log" value="1" checked="checked"/>
            </td>
        </tr>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="rs_reset_master_log">
                    <?php _e('Reset Master Logs', 'rewardsystem'); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="rs_reset_master_log" id="rs_reset_master_log" value="1" checked="checked"/>
            </td>
        </tr>
        <tr valign="top">
            <td>
            </td>
            <td>
                <input type="submit" class="button-primary" name="rs_reset_data_submit" id="rs_reset_data_submit" value="Reset Data" /><br>
                <div class="rs_reset_success_data">

                </div>
            </td>
        </tr>
        <?php if (isset($_GET['page']) == 'rewardsystem_callback') {
                if (isset($_GET['tab'])) {
                        if ($_GET['tab'] == 'rewardsystem_reset') {
                            if ((float) $woocommerce->version <= (float) ('2.2.0')) {
                                echo RSJQueryFunction::rs_common_chosen_function('#rs_reset_selected_user_data');
                            } else { 
                                echo RSJQueryFunction::rs_common_select_function('#rs_reset_selected_user_data');
                            }
                        }
                }             
            }
        echo RSJQueryFunction::rs_common_ajax_function_to_select_user('rs_reset_selected_user_data');
        ?>        
        <script type="text/javascript">
            jQuery(function () {                  
                var initialdata = jQuery('.rs_reset_data_for_users').filter(":checked").val();              
                if (initialdata === '1') {
                    jQuery('.rs_reset_selected_users').css('display', 'none');
                } else {
                    jQuery('.rs_reset_selected_users').css('display', 'table-form-group');
                }
                //Get a Value on Change of Radio Button
                jQuery('.rs_reset_data_for_users').change(function () {
                    var presentdata = jQuery(this).filter(":checked").val();                    
                    if (presentdata === '1') {
                        jQuery('.rs_reset_selected_users').css('display', 'none');
                        jQuery('#rs_reset_master_log').parent().parent().css('display', 'block');
                    } else {
                        jQuery('.rs_reset_selected_users').css('display', 'table-row');
                        jQuery('#rs_reset_master_log').parent().parent().css('display', 'none');
                    }
                });

                jQuery('#rs_reset_data_submit').click(function () {  
                    if(confirm("Are You Sure ? Do You Want to Reset Your Data?") == true){
                        var resetoptions = jQuery('.rs_reset_data_for_users').filter(":checked").val();                        
                        var selectedusers = jQuery('#rs_export_masterlog_users_list').val();
                        var resetuserpoints = jQuery('#rs_reset_user_reward_points').filter(":checked").val();
                        var resetuserlogs = jQuery('#rs_reset_user_log').filter(":checked").val();
                        var resetmasterlogs = jQuery('#rs_reset_master_log').filter(":checked").val();
                        var dataparam = ({
                            action: 'rsresetuserdata',
                            resetdatafor: resetoptions,
                            rsselectedusers: selectedusers,
                            rsresetuserpoints: resetuserpoints,
                            rsresetuserlogs: resetuserlogs,
                            rsresetmasterlogs: resetmasterlogs,
                        });
                        jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", dataparam,
                                function (response) {        
                                    var newresponse = response.replace(/\s/g, '');
                                    if (newresponse === 'success') {                                    
                                        jQuery('.rs_reset_success_data').html("Data's Resetted Successfully");
                                        return false;
                                    }
                                });
                        return false;
                    }else{
                        return false;
                    }                    
                    
                });
            });           
        </script>
        <?php
    }
    
    public static function process_reset_data_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $table_name2 = $wpdb->prefix . 'rsrecordpoints';
        if (isset($_POST['resetdatafor'])) {
            
            if (isset($_POST['rsresetuserpoints'])) {
                $proceeduserrewardpoints = $_POST['rsresetuserpoints'];
            } else {
                $proceeduserrewardpoints = '';
            }
            if (isset($_POST['rsresetuserlogs'])) {
                $proceeduserlogs = $_POST['rsresetuserlogs'];
            } else {
                $proceeduserlogs = '';
            }
            if (isset($_POST['rsresetmasterlogs'])) {
                $proceedmasterlogs = $_POST['rsresetmasterlogs'];
            } else {
                $proceedmasterlogs = "";
            }
            if ($_POST['resetdatafor'] == '2') {   //Selected User
                if (isset($_POST['rsselectedusers'])) {
                    $user[] = $_POST['rsselectedusers'];
                    $seelcteduserid = implode(',',$user);                                        

                    
                    
                    
                        if ($proceeduserrewardpoints == '1') {
                            $wpdb->query("DELETE FROM $table_name WHERE userid  IN($seelcteduserid)");
                             if(is_array($user)){
                                if(!empty($user)){
                                foreach($user as $eachuser){
                                    delete_user_meta($eachuser,'_my_reward_points');
                                }    
                                }
                                
                            }
                        }
                        if ($proceeduserlogs == '1') {
                            $wpdb->query("UPDATE $table_name2 SET showuserlog = true WHERE userid  IN($seelcteduserid)");
                            if(is_array($user)){
                                 if(!empty($user)){
                                foreach($user as $eachuser){
                                    delete_user_meta($eachuser,'_my_points_log');
                                }
                                 }
                            }
                            
                        }
                        if ($proceedmasterlogs == '1') {                            
                            $wpdb->query("UPDATE $table_name2 SET showmasterlog = true WHERE userid  IN($seelcteduserid)");
                        }                    
                }
            } else {
                //If not then All Users
                foreach (get_users() as $eachuser) {                    
                    if ($proceeduserrewardpoints == '1') {
                        $userlists = $eachuser->ID;
                        $wpdb->query("DELETE FROM $table_name WHERE userid = $userlists");
                         delete_user_meta($userlists,'_my_reward_points');
                    }
                    if ($proceeduserlogs == '1') {                        
                        $userlists = $eachuser->ID;                        
                        $wpdb->query("UPDATE $table_name2 SET showuserlog = true WHERE userid = $userlists");
                         delete_user_meta($userlists,'_my_points_log');
                    }
                    if ($proceedmasterlogs == '1') {
                        $userlists = $eachuser->ID;                        
                        $wpdb->query("UPDATE $table_name2 SET showmasterlog = true WHERE userid = $userlists");
                       delete_option('rsoveralllog');
                    }
                }
            }
            echo "success";
        }
        exit();
    }
}
new RSFunctionForReset();