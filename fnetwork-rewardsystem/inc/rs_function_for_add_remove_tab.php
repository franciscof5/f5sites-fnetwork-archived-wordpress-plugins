<?php

class RSFunctionForAddorRemovePoints {

    public function __construct() {
        add_action('admin_head', array($this, 'rs_jquery_function_for_add_remove_tab'));

        add_action('woocommerce_admin_field_rs_add_remove_remove_reward_points', array($this, 'rs_getting_list_for_add_remove_option'));

        add_action('admin_head', array($this, 'rs_validation_for_input_field_in_add_remove_points'));
    }

    /*
     * Function to add label setting in Add/Remove Reward Points
     */

    Public static function rs_jquery_function_for_add_remove_tab() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
        // To Show the option of User Type       
                if (jQuery('#rs_select_user_type').val() == '1') {
                    jQuery('#rs_select_to_include_customers').parent().parent().hide();
                    jQuery('#rs_select_to_exclude_customers').parent().parent().hide();
                } else if (jQuery('#rs_select_user_type').val() == '2') {
                    jQuery('#rs_select_to_include_customers').parent().parent().show();
                    jQuery('#rs_select_to_exclude_customers').parent().parent().hide();
                } else {
                    jQuery('#rs_select_to_include_customers').parent().parent().hide();
                    jQuery('#rs_select_to_exclude_customers').parent().parent().show();
                }
                jQuery('#rs_select_user_type').change(function () {
                    if (jQuery('#rs_select_user_type').val() == '1') {
                        jQuery('#rs_select_to_include_customers').parent().parent().hide();
                        jQuery('#rs_select_to_exclude_customers').parent().parent().hide();
                    } else if (jQuery('#rs_select_user_type').val() == '2') {
                        jQuery('#rs_select_to_include_customers').parent().parent().show();
                        jQuery('#rs_select_to_exclude_customers').parent().parent().hide();
                    } else {
                        jQuery('#rs_select_to_include_customers').parent().parent().hide();
                        jQuery('#rs_select_to_exclude_customers').parent().parent().show();
                    }
                });
            });
        </script>
        <?php
    }

    public static function rs_getting_list_for_add_remove_option() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        global $woocommerce;
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        $enablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');

        echo RSJQueryFunction::rs_common_ajax_function_to_select_user('rs_select_to_include_customers');
        echo RSJQueryFunction::rs_common_ajax_function_to_select_user('rs_select_to_exclude_customers');
        ?>
        <style type="text/css">
            p.submit {
                display:none;
            }
            #mainforms {
                display:none;
            }

        </style>        
        <form name="rs_addremove" method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th class="titledesc" scope="row">
                            <label for="rs_select_user_type"><?php _e('User Type', 'rewardsystem'); ?> </label>
                        </th>
                        <td>
                            <select name="rs_select_user_type"  id="rs_select_user_type" class="short rs_select_user_type">
                                <option value="1"><?php echo __('All User', 'rewardsystem'); ?></option>
                                <option value="2"><?php echo __('Include User', 'rewardsystem'); ?></option>
                                <option value="3"><?php echo __('Exclude User', 'rewardsystem'); ?></option>
                            </select>  
                        </td>
                    </tr>
                    <?php if ((float) $woocommerce->version <= (float) ('2.2.0')) { ?>

                        <tr valign="top">
                            <th class="titledesc" scope="row">
                                <label for="rs_select_to_include_customers"><?php _e('Select to Include Username/Email', 'rewardsystem'); ?> </label>
                            </th>
                            <td>
                                <select name="rs_select_to_include_customers" multiple="multiple" id="rs_select_to_include_customers" class="short rs_select_to_include_customers">
                                    <?php
                                    $json_ids = array();

                                    $getuser = get_option('rs_select_to_include_customers');
                                    if ($getuser != "") {
                                        $listofuser = $getuser;
                                        if (!is_array($listofuser)) {
                                            $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                                        } else {
                                            $userids = $listofuser;
                                        }
                                        foreach ($userids as $userid) {
                                            $user = get_user_by('id', $userid);
                                            ?>
                                            <option value="<?php echo $userid; ?>" selected="selected"><?php echo esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email) . ')'; ?></option>

                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr valign="top">
                            <th class="titledesc" scope="row">
                                <label for="rs_select_to_include_customers"><?php _e('Select to Include Username/Email', 'rewardsystem'); ?> </label>
                            </th>
                            <td>
                                <input type="hidden" class="wc-customer-search" name="rs_select_to_include_customers" id="rs_select_to_include_customers" data-multiple="true" data-placeholder="<?php _e('Search Users', 'rewardsystem'); ?>" data-selected="<?php
                        $json_ids = array();
                        $getuser = get_option('rs_select_to_include_customers');
                        if ($getuser != "") {
                            $listofuser = $getuser;
                            if (!is_array($listofuser)) {
                                $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                            } else {
                                $userids = $listofuser;
                            }

                            foreach ($userids as $userid) {
                                $user = get_user_by('id', $userid);
                                $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email) . ')';
                            }
                            echo esc_attr(json_encode($json_ids));
                        }
                        ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" data-allow_clear="true" />
                            </td>
                        </tr>
                        <?php
                    }
                    ?>



                    <?php if ((float) $woocommerce->version <= (float) ('2.2.0')) { ?>
                        <tr valign="top">
                            <th class="titledesc" scope="row">
                                <label for="rs_select_to_exclude_customers"><?php _e('Select to Exclude Username/Email', 'rewardsystem'); ?> </label>
                            </th>
                            <td>
                                <select name="rs_select_to_exclude_customers" multiple="multiple" id="rs_select_to_exclude_customers" class="short rs_select_to_exclude_customers">
                                    <?php
                                    $json_ids = array();
                                    $getuser = get_option('rs_select_to_exclude_customers');
                                    if ($getuser != "") {
                                        $listofuser = $getuser;
                                        if (!is_array($listofuser)) {
                                            $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                                        } else {
                                            $userids = $listofuser;
                                        }
                                        foreach ($userids as $userid) {
                                            $user = get_user_by('id', $userid);
                                            ?>
                                            <option value="<?php echo $userid; ?>" selected="selected"><?php echo esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email) . ')'; ?></option>

                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr valign="top">
                            <th class="titledesc" scope="row">
                                <label for="rs_select_to_exclude_customers"><?php _e('Select to Exclude Username/Email', 'rewardsystem'); ?> </label>
                            </th>
                            <td>
                                <input type="hidden" class="wc-customer-search" name="rs_select_to_exclude_customers" id="rs_select_to_exclude_customers" data-multiple="true" data-placeholder="<?php _e('Search Users', 'rewardsystem'); ?>" data-selected="<?php
                        $json_ids = array();
                        $getuser = get_option('rs_select_to_exclude_customers');
                        if ($getuser != "") {
                            $listofuser = $getuser;
                            if (!is_array($listofuser)) {
                                $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                            } else {
                                $userids = $listofuser;
                            }

                            foreach ($userids as $userid) {
                                $user = get_user_by('id', $userid);
                                $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email) . ')';
                            }
                            echo esc_attr(json_encode($json_ids));
                        }
                        ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" data-allow_clear="true" />
                            </td>
                        </tr>
                        <?php
                    }
                    ?>


                    <tr valign="top">
                        <th class="titledesc" scope="row">
                            <label for="rs_reward_addremove_points"><?php _e('Enter Points', 'rewardsystem'); ?></label>
                        </th>
                        <td class="forminp forminp-text">
                            <input type="text" class="" value="" style="min-width:150px;" required='required' id="rs_reward_addremove_points" name="rs_reward_addremove_points"> 	                    
                        </td>
                    </tr>
                    <tr valign="top">
                        <th class="titledesc" scope="row">
                            <label for="rs_reward_addremove_reason"><?php _e('Reason in Detail'); ?></label>
                        </th>
                        <td class="forminp forminp-text">                          
                            <textarea cols='40' rows='5' name='rs_reward_addremove_reason' required='required'></textarea>
                        </td>
                    </tr>
                    <tr valign='top'>
                        <td>
                            <input type='submit' name='rs_remove_points' id='rs_remove_points'  class='button-primary' value='Remove Points'/>

                        </td>
                        <td>
                            <input type='submit' name='rs_add_points' id='rs_add_points' class='button-primary' value='Add Points'/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>   


        <?php
                if (isset($_POST['rs_select_user_type'])) {
            if (isset($_POST['rs_add_points'])) {
                $usertype = $_POST['rs_select_user_type'];
                if ($usertype == 1) {
                    $array = get_users();
                    foreach ($array as $arrays) {
                        if (isset($_POST['rs_add_points'])) {
                            $noofdays = get_option('rs_point_to_be_expire');
                            if (($noofdays != '0') && ($noofdays != '')) {
                                $date = time() + ($noofdays * 24 * 60 * 60);
                            } else {
                                $date = '999999999999';
                            }
                            $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                            $user_id = $arrays->ID;
                            $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                            $userpoints = $my_rewards[0]['availablepoints'];
                            $updatedpoints = $_POST['rs_reward_addremove_points'] + $userpoints;
                            $rs_new_points_to_add = $_POST['rs_reward_addremove_points'];
                            if ($enablemaxpoints == 'yes') {
                                if (($updatedpoints <= $restrictuserpoints) || ($restrictuserpoints == '')) {
                                    $updatedpoints = $updatedpoints;
                                } else {
                                    $updatedpoints = $restrictuserpoints;
                                }
                            }
                            $reasonindetail = $_POST['rs_reward_addremove_reason'];
                            $addedpoints = $_POST['rs_reward_addremove_points'];
                            $totalearnedpoints = $addedpoints;
                            RSPointExpiry::insert_earning_points($user_id, $addedpoints, '0', $date, 'MAP', '', $totalearnedpoints, '0', $reasonindetail);

                            $equearnamt = RSPointExpiry::earning_conversion_settings($addedpoints);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                            RSPointExpiry::record_the_points($user_id, $addedpoints, '0', $date, 'MAP', $equearnamt, '0', '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                            $list_user_id[] = $user_id;
                            //$newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                            //wp_safe_redirect($newredirect);
                        }
                    }
                    $countuser = count($list_user_id);
                    echo '<span style="color: green;font-size:18px;">Points successfully added to ' . $countuser . ' users</span>';
                } elseif ($usertype == 2) {

                    $includeuser = $_POST['rs_select_to_include_customers'];
                    $include = explode(",", $includeuser);
                    if ($includeuser != '') {
                        foreach ($include as $includes) {
                            if (isset($_POST['rs_add_points'])) {
                                $noofdays = get_option('rs_point_to_be_expire');
                                if (($noofdays != '0') && ($noofdays != '')) {
                                    $date = time() + ($noofdays * 24 * 60 * 60);
                                } else {
                                    $date = '999999999999';
                                }
                                $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                                $user_id = $includes;
                                $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                                $userpoints = $my_rewards[0]['availablepoints'];
                                $updatedpoints = $_POST['rs_reward_addremove_points'] + $userpoints;
                                $rs_new_points_to_add = $_POST['rs_reward_addremove_points'];
                                if ($enablemaxpoints == 'yes') {
                                    if (($updatedpoints <= $restrictuserpoints) || ($restrictuserpoints == '')) {
                                        $updatedpoints = $updatedpoints;
                                    } else {
                                        $updatedpoints = $restrictuserpoints;
                                    }
                                }
                                $reasonindetail = $_POST['rs_reward_addremove_reason'];
                                $addedpoints = $_POST['rs_reward_addremove_points'];
                                $totalearnedpoints = $addedpoints;
                                RSPointExpiry::insert_earning_points($user_id, $addedpoints, '0', $date, 'MAP', '', $totalearnedpoints, '0', $reasonindetail);
                                $list_user_id[] = $user_id;
                                $equearnamt = RSPointExpiry::earning_conversion_settings($addedpoints);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                                RSPointExpiry::record_the_points($user_id, $addedpoints, '0', $date, 'MAP', $equearnamt, '0', '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                                //$newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                                //wp_safe_redirect($newredirect);
                            }
                        }
                        $countuser = count($list_user_id);
                        if($countuser > '1'){
                        echo '<span style="color: green;font-size:18px;">Points successfully added to ' . $countuser . ' users</span>';
                    }else{
                        echo '<span style="color: green;font-size:18px;">Points successfully added to ' . $countuser . ' user</span>';
                    }
                    }
                } else {

                    $excludeuser = $_POST['rs_select_to_exclude_customers'];
                    $exclude = explode(",", $excludeuser);
                    if ($excludeuser != '') {
                        $alluser = get_users();
                        foreach ($alluser as $user) {
                            $user_id = $user->ID;
                            if (!in_array($user_id, $exclude)) {
                                if (isset($_POST['rs_add_points'])) {
                                    $noofdays = get_option('rs_point_to_be_expire');
                                    if (($noofdays != '0') && ($noofdays != '')) {
                                        $date = time() + ($noofdays * 24 * 60 * 60);
                                    } else {
                                        $date = '999999999999';
                                    }
                                    $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                                    $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                                    $userpoints = $my_rewards[0]['availablepoints'];
                                    $updatedpoints = $_POST['rs_reward_addremove_points'] + $userpoints;
                                    $rs_new_points_to_add = $_POST['rs_reward_addremove_points'];
                                    if ($enablemaxpoints == 'yes') {
                                        if (($updatedpoints <= $restrictuserpoints) || ($restrictuserpoints == '')) {
                                            $updatedpoints = $updatedpoints;
                                        } else {
                                            $updatedpoints = $restrictuserpoints;
                                        }
                                    }
                                    $reasonindetail = $_POST['rs_reward_addremove_reason'];
                                    $addedpoints = $_POST['rs_reward_addremove_points'];
                                    $totalearnedpoints = $addedpoints;
                                    RSPointExpiry::insert_earning_points($user_id, $addedpoints, '0', $date, 'MAP', '', $totalearnedpoints, '0', $reasonindetail);
                                    $list_user_id[] = $user_id;
                                    $equearnamt = RSPointExpiry::earning_conversion_settings($addedpoints);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                                    RSPointExpiry::record_the_points($user_id, $addedpoints, '0', $date, 'MAP', $equearnamt, '0', '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                                    // $newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                                    //wp_safe_redirect($newredirect);
                                }
                            }
                        }

                        $countuser = count($list_user_id);
                        if($countuser > '1'){
                        echo '<span style="color: green;font-size:18px;">Points successfully added to ' . $countuser . ' users</span>';
                    }else{
                        echo '<span style="color: green;font-size:18px;">Points successfully added to ' . $countuser . ' user</span>';
                    }
                    }
                }
            }

            if (isset($_POST['rs_remove_points'])) {
                $usertype = $_POST['rs_select_user_type'];
                if ($usertype == 1) {
                    $alluser = count_users();
                    $countuser = $alluser['total_users'];
                    $array = get_users();

                    foreach ($array as $arrays) {
                        //if (isset($_POST['rs_remove_points'])) {
                        $noofdays = get_option('rs_point_to_be_expire');
                        if (($noofdays != '0') && ($noofdays != '')) {
                            $date = time() + ($noofdays * 24 * 60 * 60);
                        } else {
                            $date = '999999999999';
                        }
                        $user_id = $arrays->ID;
                        $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                        $userpoints = $my_rewards[0]['availablepoints'];
                        $updatedpoints = $userpoints - $_POST['rs_reward_addremove_points'];
                        $reasonindetail = $_POST['rs_reward_addremove_reason'];
                        $removedpoints = $_POST['rs_reward_addremove_points'];

                        if ($removedpoints <= $userpoints) {
                            $pointsredeemed = RSPointExpiry::perform_calculation_with_expiry($removedpoints, $user_id);
                            $equredeemamt = RSPointExpiry::earning_conversion_settings($removedpoints);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                            RSPointExpiry::record_the_points($user_id, '0', $removedpoints, $date, 'MRP', '', $equredeemamt, '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                            $list_user_id[] = $user_id;
                            //var_dump($list_user_id);
                            //$newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                            //wp_safe_redirect($newredirect);
                        }

                        //}
                    }
                         $successcount = isset($list_user_id)? count($list_user_id):0;
                        //var_dump($successcount);
                        $failurecount = $countuser - $successcount;
                        //var_dump($failurecount);
                        if ($successcount != '0' && $failurecount == '0') {
                            if($successcount > '1'){
                            echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>';
                            }else if($successcount <= '1'){
                                echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>';
                            }
                        } else if ($successcount != '0' && $failurecount != '0') {
                           if($successcount > '1' && $failurecount <= '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }else if($successcount <= '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else if($successcount > '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else if($successcount == '1' && $failurecount == '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        } else if ($successcount == '0' && $failurecount != '0') {
                            if($failurecount > '1'){
                            echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else{
                                echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        }
                } elseif ($usertype == 2) {

                    $includeuser = $_POST['rs_select_to_include_customers'];
                    $include = explode(",", $includeuser);
                    $countuser = count($include);
                    if ($includeuser != '') {
                        foreach ($include as $includes) {
                            if (isset($_POST['rs_remove_points'])) {
                                $noofdays = get_option('rs_point_to_be_expire');
                                if (($noofdays != '0') && ($noofdays != '')) {
                                    $date = time() + ($noofdays * 24 * 60 * 60);
                                } else {
                                    $date = '999999999999';
                                }
                                $user_id = $includes;
                                $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                                $userpoints = $my_rewards[0]['availablepoints'];
                                $updatedpoints = $userpoints - $_POST['rs_reward_addremove_points'];
                                $reasonindetail = $_POST['rs_reward_addremove_reason'];
                                $removedpoints = $_POST['rs_reward_addremove_points'];

                                if ($removedpoints <= $userpoints) {
                                    $pointsredeemed = RSPointExpiry::perform_calculation_with_expiry($removedpoints, $user_id);
                                    $equredeemamt = RSPointExpiry::earning_conversion_settings($removedpoints);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                                    RSPointExpiry::record_the_points($user_id, '0', $removedpoints, $date, 'MRP', '', $equredeemamt, '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                                    $list_user_id[] = $user_id;
                                    //$newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                                    //wp_safe_redirect($newredirect);
                                }
                            }
                        }
                        
                        $successcount = isset($list_user_id)? count($list_user_id):0;
                        
                        $failurecount = $countuser - $successcount;
                        if ($successcount != '0' && $failurecount == '0') {
                            if($successcount > '1'){
                            echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>';
                        
                            }else{
                                echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>';
                            }
                        } else if ($successcount != '0' && $failurecount != '0') {
                           if($successcount > '1' && $failurecount <= '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }else if($successcount <= '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else if($successcount > '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                        }else if($successcount == '1' && $failurecount == '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        } else if ($successcount == '0' && $failurecount != '0') {
                            if($failurecount > '1'){
                            echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else{
                                echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        }
                    }
                } else {

                    $excludeuser = $_POST['rs_select_to_exclude_customers'];
                    $alluser = count_users();
                    $countusers = $alluser['total_users'];

                    $exclude = explode(",", $excludeuser);
                    $countuser = count($exclude);
                    $updateduser = $countusers - $countuser;

                    if ($excludeuser != '') {
                        $alluser = get_users();
                        foreach ($alluser as $user) {
                            $user_id = $user->ID;
                            if (!in_array($user_id, $exclude)) {
                                if (isset($_POST['rs_remove_points'])) {
                                    $noofdays = get_option('rs_point_to_be_expire');
                                    if (($noofdays != '0') && ($noofdays != '')) {
                                        $date = time() + ($noofdays * 24 * 60 * 60);
                                    } else {
                                        $date = '999999999999';
                                    }

                                    $my_rewards = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid = $user_id", ARRAY_A);
                                    $userpoints = $my_rewards[0]['availablepoints'];
                                    $updatedpoints = $userpoints - $_POST['rs_reward_addremove_points'];
                                    $reasonindetail = $_POST['rs_reward_addremove_reason'];
                                    $removedpoints = $_POST['rs_reward_addremove_points'];

                                    if ($removedpoints <= $userpoints) {
                                        $pointsredeemed = RSPointExpiry::perform_calculation_with_expiry($removedpoints, $user_id);
                                        $equredeemamt = RSPointExpiry::earning_conversion_settings($removedpoints);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);
                                        RSPointExpiry::record_the_points($user_id, '0', $removedpoints, $date, 'MRP', '', $equredeemamt, '', '', '', '', $reasonindetail, $totalpoints, '', '0');
                                        $list_user_id[] = $user_id;
                                    }
                                }
                            }
                        }
                         $successcount = isset($list_user_id)? count($list_user_id):0;
                        $failurecount = $updateduser - $successcount;
                        if ($successcount != '0' && $failurecount == '0') {
                            if($successcount > '1'){
                            echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>';
                        
                            }else{
                                echo '<span style="color: green; font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>';
                            }
                        } else if ($successcount != '0' && $failurecount != '0') {
                           if($successcount > '1' && $failurecount <= '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }else if($successcount <= '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else if($successcount > '1' && $failurecount > '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' users</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                            }else if($successcount == '1' && $failurecount == '1' ){
                                echo '<span style="color: green;font-size:18px;">Points successfully removed to ' . $successcount . ' user</span>,<span style="color: red;font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        } else if ($successcount == '0' && $failurecount != '0') {
                            if($failurecount > '1'){
                            echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' users</span>';
                        
                            }else{
                                echo '<span style="color: red; font-size:18px;">Process failed to ' . $failurecount . ' user</span>';
                            }
                        }
                        //$newredirect = esc_url_raw(add_query_arg('saved', 'true', get_permalink()));
                        //wp_safe_redirect($newredirect);                    
                    }
                }
            }
        }
    }

    public static function rs_validation_for_input_field_in_add_remove_points() {
        ?>

        <script type="text/javascript">
            jQuery(function () {
                jQuery('body').on('blur', '#rs_reward_addremove_points[type=text]', function () {
                    jQuery('.wc_error_tip').fadeOut('100', function () {
                        jQuery(this).remove();
                    });

                    return this;
                });

                jQuery('body').on('keyup change', '#rs_reward_addremove_points[type=text]', function () {
                    var value = jQuery(this).val();
                    console.log(woocommerce_admin.i18n_mon_decimal_error);
                    var regex = new RegExp("[^\+0-9\%.\\" + woocommerce_admin.mon_decimal_point + "]+", "gi");
                    var newvalue = value.replace(regex, '');

                    if (value !== newvalue) {
                        jQuery(this).val(newvalue);
                        if (jQuery(this).parent().find('.wc_error_tip').size() == 0) {
                            var offset = jQuery(this).position();
                            jQuery(this).after('<div class="wc_error_tip">' + woocommerce_admin.i18n_mon_decimal_error + " Negative Values are not allowed" + '</div>');
                            jQuery('.wc_error_tip')
                                    .css('left', offset.left + jQuery(this).width() - (jQuery(this).width() / 2) - (jQuery('.wc_error_tip').width() / 2))
                                    .css('top', offset.top + jQuery(this).height())
                                    .fadeIn('100');
                        }
                    }



                    return this;
                });



                jQuery("body").click(function () {
                    jQuery('.wc_error_tip').fadeOut('100', function () {
                        jQuery(this).remove();
                    });

                });
            });
        </script>
        <?php
    }

}

new RSFunctionForAddorRemovePoints();
