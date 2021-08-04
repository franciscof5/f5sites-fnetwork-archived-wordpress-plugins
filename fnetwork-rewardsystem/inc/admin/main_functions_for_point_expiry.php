<?php

class RSPointExpiry {

    public function __construct() {       

        $orderstatuslist = get_option('rs_order_status_control');
        if (is_array($orderstatuslist)) {
            foreach ($orderstatuslist as $value) {
                add_action('woocommerce_order_status_' . $value, array($this, 'update_earning_points_for_user'),1);
            }
        } 
                
        
        $order_status_control = get_option('rs_list_other_status');
        if (get_option('rs_list_other_status') != '') {
            foreach ($order_status_control as $order_status) {
                $orderstatuslist = get_option('rs_order_status_control');
                if (is_array($orderstatuslist)) {
                    foreach ($orderstatuslist as $value) {
                        add_action('woocommerce_order_status_' . $value . '_to_' . $order_status, array($this, 'update_revised_points_for_user'));
                    }
                }
            }
        }
       
        add_action('wp_head',array($this,'check_if_expiry'));
                
        
        add_action('wp_head',array($this,'delete_if_used'));
                         
        add_action('comment_post', array($this, 'get_reviewed_user_list'), 10, 2);
        
        if (get_option('rs_review_reward_status') == '1') {
            add_action('comment_unapproved_to_approved', array($this, 'getcommentstatus'), 10, 1);
        }
        if (get_option('rs_review_reward_status') == '2') {
            add_action('comment_unapproved', array($this, 'getcommentstatus'), 10, 1);
        }              
        
        add_action('woocommerce_update_options_rewardsystem_status', array($this,'rewards_rs_order_status_control'), 99);

        add_action('init', array($this,'rewards_rs_order_status_control'), 9999);
        
        add_action('delete_user', array($this, 'delete_referral_registered_people'));
        
        add_shortcode('rs_my_reward_points', array($this, 'myrewardpoints_total_shortcode'));
        
        add_shortcode('rs_generate_referral', array($this, 'rs_fp_rewardsystem'));
        
        add_shortcode('rs_generate_static_referral', array($this, 'shortcode_for_static_referral_link'));
        
        add_action('woocommerce_checkout_update_order_meta',array($this,'check_redeeming_in_order'),10,2);
          
    }

    /* Check Point is Valid to Redeeming
     * param1: $userid,
     * return: null, it just perform the query for mysql if the point is expired.
     */
    
   

    public static function check_if_expiry() {          
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';                        
        $userid = get_current_user_id();
        $currentdate = time();                    
        $getarraystructure = $wpdb->get_results("SELECT * FROM $table_name WHERE expirydate < $currentdate and expirydate NOT IN(999999999999) and expiredpoints IN(0) and userid=$userid", ARRAY_A);
        if(!empty($getarraystructure)){
            foreach ($getarraystructure as $key => $eacharray) {
                $wpdb->update($table_name, array('expiredpoints' => $eacharray['earnedpoints'] - $eacharray['usedpoints']), array('id' => $eacharray['id']));                
            }
        }            
        
    }
    
    public static function check_if_expiry_on_admin($user_id) {        
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';                        
        $userid = $user_id;
        $currentdate = time();                    
        $getarraystructure = $wpdb->get_results("SELECT * FROM $table_name WHERE expirydate < $currentdate and expirydate NOT IN(999999999999) and expiredpoints IN(0) and userid=$userid", ARRAY_A);
        if(!empty($getarraystructure)){
            foreach ($getarraystructure as $key => $eacharray) {
                $wpdb->update($table_name, array('expiredpoints' => $eacharray['earnedpoints'] - $eacharray['usedpoints']), array('id' => $eacharray['id']));                
            }
        }                   
    }
    
    public static function delete_if_used() {                
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';                        
        $userid = get_current_user_id();
        $currentdate = time();     
        $totalearnpoints = '';
        $totalredeempoints = '';
        $getarraystructure = $wpdb->get_results("SELECT * FROM $table_name WHERE earnedpoints=usedpoints and expiredpoints IN(0) and userid=$userid", ARRAY_A);
        if(!empty($getarraystructure)){
            foreach ($getarraystructure as $eacharray) {                
                $totalearnpoints += $eacharray['earnedpoints'];
                $totalredeempoints += $eacharray['usedpoints'];                
                update_user_meta($userid,'rs_earned_points_before_delete',$totalearnpoints);
                update_user_meta($userid,'rs_redeem_points_before_delete',$totalredeempoints);
                $wpdb->delete($table_name, array('id' => $eacharray['id']));
            }
        }
        
        $getdata = $wpdb->get_results("SELECT * FROM $table_name WHERE earnedpoints=(usedpoints+expiredpoints) and expiredpoints NOT IN(0) and userid=$userid", ARRAY_A);
        $totalexpiredpoints = '';
        if(!empty($getdata)){
            foreach ($getdata as $array) {
                $totalexpiredpoints += $array['expiredpoints'];                
                update_user_meta($userid,'rs_expired_points_before_delete',$totalexpiredpoints);
                $wpdb->delete($table_name, array('id' => $array['id']));
            }
        }        
    }

    /* Get the SUM of available Points after performing few more audits */

    public static function get_sum_of_earned_points() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $getcurrentuserid = get_current_user_id();
        $checkresults = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid=$getcurrentuserid", ARRAY_A);
        return $checkresults;
    }

    /* Get the SUM of available Points with order id */

    public static function get_sum_of_total_earned_points($userid) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $getcurrentuserid = $userid;
        $checkresults = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid=$getcurrentuserid", ARRAY_A);
        foreach($checkresults as $checkresultss){
            $checkresult = $checkresultss['availablepoints'];
        }
        return $checkresult;
    }

    /* Insert the Data based on Point Expiry */

    public static function insert_earning_points($user_id, $earned_points,$usedpoints,$date,$checkpoints,$orderid,$totalearnedpoints,$totalredeempoints,$reasonindetail = '') {
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "rspointexpiry";
        $currentdate = time();                        
        $noofday = get_option('rs_point_to_be_expire');
        $expirydate = 999999999999;
        if(($noofday == '') || ($noofday == '0')){
            $query = $wpdb->get_row("SELECT * FROM $table_name WHERE userid = $user_id and expirydate = $expirydate",ARRAY_A);
            if(!empty($query)){                                            
                 $id = $query['id'];
                 $oldearnedpoints = $query['earnedpoints'];
                 $oldearnedpoints = $oldearnedpoints + $earned_points;
                 $usedpoints = $usedpoints + $query['usedpoints'];               
                 $wpdb->update($table_name, array('earnedpoints' => $oldearnedpoints,'usedpoints'=>$usedpoints),array('id'=>$id));                                  
            }else{
                 $wpdb->insert(
                $table_name, array(
            'earnedpoints' => $earned_points,
            'usedpoints' => $usedpoints,
            'expiredpoints' =>'0',
            'userid' => $user_id,
            'earneddate' => $currentdate,
            'expirydate' => $date,
            'checkpoints' => $checkpoints,
            'orderid'=>$orderid,
            'totalearnedpoints'=>$totalearnedpoints,
            'totalredeempoints'=>$totalredeempoints,
            'reasonindetail'=>$reasonindetail
            ));
            }
        }else{
            $wpdb->insert(
                $table_name, array(
            'earnedpoints' => $earned_points,
            'usedpoints' => $usedpoints,
            'expiredpoints' =>'0',
            'userid' => $user_id,
            'earneddate' => $currentdate,
            'expirydate' => $date,
            'checkpoints' => $checkpoints,
            'orderid'=>$orderid,
            'totalearnedpoints'=>$totalearnedpoints,
            'totalredeempoints'=>$totalredeempoints,
            'reasonindetail'=>$reasonindetail
        ));
        }
    }        

    public static function record_the_points($user_id,$earned_points,$usedpoints,$date,$checkpoints,$equearnamt,$equredeemamt,$orderid,$productid,$variationid,$refuserid,$reasonindetail,$totalpoints,$nomineeid,$nomineepoints) {
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "rsrecordpoints";       
        $currentdate =  date("d-m-Y h:i:s A");         
        $wpdb->insert(
                $table_name, array(
            'earnedpoints' => $earned_points,
            'redeempoints' => $usedpoints,             
            'userid' => $user_id,            
            'earneddate' => $currentdate,
            'expirydate' => $date,
            'checkpoints' => $checkpoints,
            'earnedequauivalentamount' => $equearnamt,
            'redeemequauivalentamount' => $equredeemamt,
            'productid' => $productid,
            'variationid' => $variationid,        
            'orderid'=>$orderid,  
            'refuserid'=>$refuserid,
            'reasonindetail'=>$reasonindetail,
            'totalpoints'=> $totalpoints,
            'showmasterlog'=> "false",
            'showuserlog'=> "false",
            'nomineeid'=>$nomineeid,
            'nomineepoints'=>$nomineepoints
        ));
    }

    public static function perform_calculation_with_expiry($redeempoints, $getcurrentuserid) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $getarraystructure = $wpdb->get_results("SELECT * FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and  expiredpoints IN(0) and userid=$getcurrentuserid ORDER BY expirydate ASC", ARRAY_A);                
        if (is_array($getarraystructure)) {            
                foreach ($getarraystructure as $key => $eachrow) {
                    $getactualpoints = $eachrow['earnedpoints'] - $eachrow['usedpoints'];                    
                    if ($redeempoints >= $getactualpoints) {
                        $getusedpoints = $getactualpoints;
                        $usedpoints = $eachrow['usedpoints'] + $getusedpoints;
                        $id = $eachrow['id'];
                        $redeempoints = $redeempoints - $getactualpoints;
                        //$wpdb->update($table_name, array('usedpoints' => $eachrow['usedpoints'] + $getusedpoints), array('id' => $eachrow['id']));
                        $wpdb->query("UPDATE $table_name SET usedpoints = $usedpoints WHERE id = $id");
                        if ($redeempoints == 0) {
                            break;
                        }
                    } else {
                        $getusedpoints = $redeempoints;
                        $usedpoints = $eachrow['usedpoints'] + $getusedpoints;
                        $id = $eachrow['id'];
                        //$wpdb->update($table_name, array('usedpoints' => $eachrow['usedpoints'] + $getusedpoints), array('id' => $eachrow['id']));
                        $wpdb->query("UPDATE $table_name SET usedpoints = $usedpoints  WHERE id = $id");
                        break;
                    }
                }
            
        }
        return;
    }
        
    public static function update_revised_points_for_user($order_id) {
        $points_awarded_for_this_order = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_revised_points');
        if($points_awarded_for_this_order != 'yes'){
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $table_name2 = $wpdb->prefix . 'rsrecordpoints';
        global $woocommerce;
        $termid = '';
        $order = new WC_Order($order_id);         
        $redeempoints = self::update_revised_reward_points_to_user($order_id,$order->user_id);         
        $noofdays = get_option('rs_point_to_be_expire');
        if($redeempoints != 0){
         $equredeemamt = self::redeeming_conversion_settings($redeempoints);         
         if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
            self::insert_earning_points($order->user_id,$redeempoints,'0', $date,'RVPFRP',$order_id,$redeempoints,'0',''); 
            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
            self::record_the_points($order->user_id, $redeempoints,'0',$date,'RVPFRP','0',$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
        }   
        
       
        $restrictuserpoints = get_option('rs_max_earning_points_for_user');
        $enabledisablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');       
        /* Reward Points For Using Payment Gateway Method */                
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        $checkredeeming = self::check_redeeming_in_order($order_id,$order->user_id);
        $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
        if($enableoption == 'yes'){
             if($checkredeeming == false){
                $getpaymentgatewayused = RSMemberFunction::user_role_based_reward_points($order->user_id, get_option('rs_reward_payment_gateways_' . $order->payment_method));
                if ($getpaymentgatewayused != '') {                     
                    $totalearnedpoints = '0';
                    $totalredeempoints = '0';
                    $check_points_payment = self::get_sum_of_total_earned_points($order->user_id);
                    $totalpoints = $check_points_payment - $getpaymentgatewayused;                
                    $equredeemamt = self::redeeming_conversion_settings($getpaymentgatewayused);    
                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                    self::record_the_points($order->user_id, '0',$getpaymentgatewayused,$date,'RVPFRPG',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                    self::insert_earning_points($order->user_id, '0',$getpaymentgatewayused,$date,'RVPFRPG',$order_id,$totalearnedpoints,$totalredeempoints,'');                                
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                    $totalredeempoints = $redeempoints;               
                    $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                

                }
             }
        }else{
            $getpaymentgatewayused = RSMemberFunction::user_role_based_reward_points($order->user_id, get_option('rs_reward_payment_gateways_' . $order->payment_method));
            if ($getpaymentgatewayused != '') {                     
                $totalearnedpoints = '0';
                $totalredeempoints = '0';
                $check_points_payment = self::get_sum_of_total_earned_points($order->user_id);
                $totalpoints = $check_points_payment - $getpaymentgatewayused;                
                $equredeemamt = self::redeeming_conversion_settings($getpaymentgatewayused);    
                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                self::record_the_points($order->user_id, '0',$getpaymentgatewayused,$date,'RVPFRPG',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                self::insert_earning_points($order->user_id, '0',$getpaymentgatewayused, $date,'RVPFRPG',$order_id,$totalearnedpoints,$totalredeempoints,'');                                
                $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                $totalredeempoints = $redeempoints;               
                $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                
            }
        }
        
         $redeempoints = 0;
            /* Reward Points For Purchasing the Product */       
            foreach ($order->get_items() as $item) {
               
                    $productid = $item['product_id'];
                    $variationid = $item['variation_id'] == '0' || '' ? '0' : $item['variation_id'];
                    $itemquantity = $item['qty'];
                    $orderuserid = $order->user_id;                    
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                        }
                    }
                    //For Inserting Reward Points
                    $checked_level_for_reward_points = self::check_level_of_enable_reward_point($productid, $variationid, $termid);
                    $equearnamt = '';
                    $equredeemamt = '';
                    self::rs_insert_the_selected_level_revised_reward_points($redeempoints,$checked_level_for_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$equearnamt,$equredeemamt,$order_id);                    
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;                               
                    $totalredeempoints = $redeempoints;                    
                    $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                                                                          

                    $referreduser = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, '_referrer_name');
                    if ($referreduser != '') {
                    //For Inserting Referral Reward Points
                    $checked_level_for_referral_reward_points = self::check_level_of_enable_referral_reward_point($productid, $variationid, $termid);
                    self::rs_insert_the_selected_level_revised_referral_reward_points($redeempoints,$referreduser,$equearnamt,$equredeemamt,$checked_level_for_referral_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);                    
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                    $totalredeempoints = $redeempoints;
                    $equredeemamt = self::redeeming_conversion_settings($totalredeempoints);
                    $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                    

                    }                                        
                    //self::update_revised_reward_points_to_user($order_id,$orderuserid);                                    
            }  
          RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($order_id, 'rs_revised_points','yes');  
        }
    }
    
    public static function update_revised_reward_points_to_user($order_id,$orderuserid) {
        // Inside Loop  
        $order = new WC_Order($order_id);
        $rewardpointscoupons = $order->get_items(array('coupon'));
        $getuserdatabyid = get_user_by('id', $orderuserid);
        $getusernickname = $getuserdatabyid->user_login;
        $maincouponchecker = 'sumo_' . strtolower($getusernickname);
        foreach ($rewardpointscoupons as $couponcode => $value) {
            if ($maincouponchecker == $value['name']) {
                if (get_option('rewardsystem_looped_over_coupon' . $order_id) != '1') {
                    $getuserdatabyid = get_user_by('id', $orderuserid);
                    $getusernickname = $getuserdatabyid->user_login;
                    $getcouponid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($orderuserid, 'redeemcouponids');
                    $currentamount = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($getcouponid, 'coupon_amount');                    
                    if ($currentamount >= $value['discount_amount']) {
                        $current_conversion = get_option('rs_redeem_point');
                        $point_amount = get_option('rs_redeem_point_value');
                        $couponamt = get_post_meta($order_id,'fp_rs_redeemed_points_value',true);
                        $redeemedamount = $couponamt * $current_conversion;
                        $redeemedpoints = $redeemedamount / $point_amount;                                                
                    }
                    return $redeemedpoints;
                    update_option('rewardsystem_looped_over_coupon' . $order_id, '1');
                }
            }
        }        
    }
    
    public static function rs_insert_the_selected_level_revised_reward_points($pointsredeemed,$level, $productid, $variationid, $itemquantity, $orderuserid, $termid,$equearnamt,$equredeemamt,$order_id) {
        
        $variable_product1 = new WC_Product_Variation($variationid);
        if (get_option('rs_set_price_percentage_reward_points') == '1') {
            $variationregularprice = $variable_product1->regular_price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_regular_price') : $variationregularprice;
        } else {
            $variationregularprice = $variable_product1->price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_price') : $variationregularprice;
        }

        do_action_ref_array('rs_update_points_for_referral_simple', array(&$getregularprice, &$item));
        
        do_action_ref_array('rs_update_points_for_variable', array(&$getregularprice, &$item));
        
        $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_reward_rule');
        $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_points');
        $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        $pointforconversion = get_option('rs_earn_point');
        $pointforconversionvalue = get_option('rs_earn_point_value');
        $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        $rewardpercentpl = $rewardpercentforproductlevel / 100;
        $getaveragepoints = $rewardpercentpl * $getregularprice;
        $pointswithvalue = $getaveragepoints * $pointforconversion;
        $productlevelrewardpercent = $pointswithvalue / $pointforconversionvalue;
        
        if (($productid != '') || ($variationid != '') || ($variationid != '0')) {
            $rewardpoints = array('0');
            $rewardpercent = array('0');
            $categorylist = wp_get_post_terms($productid, 'product_cat');
            $getcount = count($categorylist);
            $term = get_the_terms($productid, 'product_cat');
            if (is_array($term)) {
                foreach ($term as $terms) {
                    $termid = $terms->term_id;                            
            if($getcount > 1){                
                    $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
                    $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
                    if($rewardpointsforcategory == ''){
                        $rewardpoints[] = get_option('rs_global_reward_points');
                    }else{
                        $rewardpoints[] = $rewardpointsforcategory;
                    }
                    $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
                    if($rewardpercentforcategory == ''){
                        $pointforconversion = get_option('rs_earn_point');
                        $pointforconversionvalue = get_option('rs_earn_point_value');
                        $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
                        $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                        $pointswithvalue = $getaveragepoints * $pointforconversion;
                        $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;                        
                    }else{
                        $pointforconversion = get_option('rs_earn_point');
                        $pointforconversionvalue = get_option('rs_earn_point_value');
                        $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent') / 100;
                        $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                        $pointswithvalue = $getaveragepoints * $pointforconversion;
                        $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;                        
                    }                                
            }else {            
            $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
            $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
            if($rewardpointsforcategory == ''){
                 $rewardpoints[] = get_option('rs_global_reward_points');
            }else{
                 $rewardpoints[] = $rewardpointsforcategory;
            }
            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
            if($rewardpercentforcategory == ''){
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
            }else{
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent') / 100;
                $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;   
            }                        
            }
                }
            }
            $categorylevelrewardpoints = max($rewardpoints);
            $categorylevelrewardpercent = max($rewardpercent);
        }
        $global_reward_type = get_option('rs_global_reward_type');
        $global_rewardpoints = get_option('rs_global_reward_points');          
        $pointforconversion = get_option('rs_earn_point');
        $pointforconversionvalue = get_option('rs_earn_point_value');
        $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
        $getaveragepoints = $get_global_rewardpercent * $getregularprice;
        $pointswithvalue = $getaveragepoints * $pointforconversion;
        $global_rewardpercent = $pointswithvalue / $pointforconversionvalue; 
        $totalearnedpoints = '0';
        $totalredeempoints = '0';
        
        $checkredeeming = self::check_redeeming_in_order($order_id,$orderuserid);
        $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }       
        switch ($level) {
            case '0':
                self::insert_earning_points($orderuserid,$pointsredeemed,'0', $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                $equearnamt = self::earning_conversion_settings($pointsredeemed);
                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                self::record_the_points($orderuserid,'0',$pointsredeemed,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                break;
            case '1':
                if ($productlevelrewardtype == '1') {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                                                
                            self::insert_earning_points($orderuserid,$pointsredeemed,$productlevelrewardpointss, $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0',$productlevelrewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                                            
                        self::insert_earning_points($orderuserid,$pointsredeemed,$productlevelrewardpointss,$date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0',$productlevelrewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                } else {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                                                
                            self::insert_earning_points($orderuserid,$pointsredeemed, $productlevelrewardpercentss,$date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0', $productlevelrewardpercentss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                                            
                        self::insert_earning_points($orderuserid,$pointsredeemed, $productlevelrewardpercentss, $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0', $productlevelrewardpercentss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                }
                break;
            case '2':
                if ($categorylevelrewardtype == '1') {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                    
                            self::insert_earning_points($orderuserid,$pointsredeemed, $categorylevelrewardpointss, $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0', $categorylevelrewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                    
                        self::insert_earning_points($orderuserid,$pointsredeemed, $categorylevelrewardpointss,$date, 'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0', $categorylevelrewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                } else {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                    
                            self::insert_earning_points($orderuserid,$pointsredeemed, $categorylevelrewardpercents,$date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0', $categorylevelrewardpercents,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                    
                        self::insert_earning_points($orderuserid,$pointsredeemed, $categorylevelrewardpercents,$date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0', $categorylevelrewardpercents,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                }
                break;
            case '3':
                if ($global_reward_type == '1') {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                    
                            self::insert_earning_points($orderuserid,$pointsredeemed, $global_rewardpointss, $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0', $global_rewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                    
                        self::insert_earning_points($orderuserid,$pointsredeemed, $global_rewardpointss,$date, 'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0', $global_rewardpointss,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                } else {
                    if($enableoption == 'yes'){
                        if($checkredeeming == false){
                            $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                    
                            self::insert_earning_points($orderuserid,$pointsredeemed, $global_rewardpercents, $date,'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                            $equearnamt = self::earning_conversion_settings($pointsredeemed);
                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                            self::record_the_points($orderuserid,'0', $global_rewardpercents,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                        }
                    }else{
                        $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                    
                        self::insert_earning_points($orderuserid,$pointsredeemed, $global_rewardpercents,$date, 'RVPFPPRP',$order_id,$totalearnedpoints,$totalredeempoints,'');
                        $equearnamt = self::earning_conversion_settings($pointsredeemed);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                        self::record_the_points($orderuserid,'0', $global_rewardpercents,$date,'RVPFPPRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');
                    }                    
                }
                break;
        }
    }
    
    public static function rs_insert_the_selected_level_revised_referral_reward_points($pointsredeemed,$referreduser,$equearnamt,$equredeemamt,$level, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail) {
        
        $variable_product1 = new WC_Product_Variation($variationid);
        if (get_option('rs_set_price_percentage_reward_points') == '1') {
            $variationregularprice = $variable_product1->regular_price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_regular_price') : $variationregularprice;
        } else {
            $variationregularprice = $variable_product1->price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_price') : $variationregularprice;
        }

        do_action_ref_array('rs_update_points_for_referral_simple', array(&$getregularprice, &$item));
        
        do_action_ref_array('rs_update_points_for_variable', array(&$getregularprice, &$item));
        $user_info = new WP_User($orderuserid);
        $registered_date = $user_info->user_registered;
        $limitation = false;
        $modified_registered_date = date('Y-m-d h:i:sa', strtotime($registered_date));
        $delay_days = get_option('_rs_select_referral_points_referee_time_content');
        $checking_date = date('Y-m-d h:i:sa', strtotime($modified_registered_date . ' + ' . $delay_days . ' days '));
        $modified_checking_date = strtotime($checking_date);
        $current_date = date('Y-m-d h:i:sa');
        $modified_current_date = strtotime($current_date);
        //Is for Immediatly
        if (get_option('_rs_select_referral_points_referee_time') == '1') {
            $limitation = true;
        } else {
            // Is for Limited Time with Number of Days
            if ($modified_current_date > $modified_checking_date) {
                $limitation = true;
            } else {
                $limitation = false;
            }
        }

        if ($limitation == true) {
            $refuser = get_user_by('login', $referreduser);
            if ($refuser != false) {

                $myid = $refuser->ID;
            } else {
                $myid = $referreduser;
            }
            $banning_type = FPRewardSystem::check_banning_type($myid);
            if ($banning_type != 'earningonly' && $banning_type != 'both') {
                $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referral_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_referral_reward_rule');
                $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_points');                                
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $rewardpercentpl = $rewardpercentforproductlevel / 100;
                $getaveragepoints = $rewardpercentpl * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $productlevelrewardpercent = $pointswithvalue / $pointforconversionvalue;

        
                if (($productid != '') || ($variationid != '') || ($variationid != '0')) {
                    $rewardpoints = array('0');
                    $rewardpercent = array('0');
                    $categorylist = wp_get_post_terms($productid, 'product_cat');
                    $getcount = count($categorylist);
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                    if ($getcount > 1) {                                                   
                            $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                            $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                            if ($rewardpointsforcategory == '') {
                                $rewardpoints[] = get_option('rs_global_referral_reward_point');
                            } else {
                                $rewardpoints[] = $rewardpointsforcategory;
                            }
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                            if ($rewardpercentforcategory == '') {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            } else {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                                $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            }                                                
                    } else {                        
                        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                        $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                        if ($rewardpointsforcategory == '') {
                            $rewardpoints[] = get_option('rs_global_referral_reward_point');
                        } else {
                            $rewardpoints[] = $rewardpointsforcategory;
                        }
                        $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                        if($rewardpercentforcategory == ''){
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                            $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }else {
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                            $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }                         
                    }
                    
                        }
                    }
                    $categorylevelrewardpoints = max($rewardpoints);
                    $categorylevelrewardpercent = max($rewardpercent);
                }
                $global_reward_type = get_option('rs_global_referral_reward_type');
                $global_rewardpoints = get_option('rs_global_referral_reward_point');                               
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $global_rewardpercent = $pointswithvalue / $pointforconversionvalue;  
                
                $checkredeeming = self::check_redeeming_in_order($order_id,$orderuserid);
                $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
                $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
                switch ($level) {
                    case '1':
                        if ($productlevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                    self::insert_earning_points($myid,$pointsredeemed, $productlevelrewardpointss,$date, 'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $productlevelrewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                self::insert_earning_points($myid,$pointsredeemed, $productlevelrewardpointss,$date, 'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $productlevelrewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                    self::insert_earning_points($myid,$pointsredeemed, $productlevelrewardpercentss,$date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $productlevelrewardpercentss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                self::insert_earning_points($myid,$pointsredeemed, $productlevelrewardpercentss,$date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $productlevelrewardpercentss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        }
                        break;
                    case '2':
                        if ($categorylevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                    self::insert_earning_points($myid,$pointsredeemed, $categorylevelrewardpointss,$date, 'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $categorylevelrewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                self::insert_earning_points($myid,$pointsredeemed, $categorylevelrewardpointss,$date, 'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $categorylevelrewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                    self::insert_earning_points($myid,$pointsredeemed, $categorylevelrewardpercents,$date, 'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $categorylevelrewardpercents,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                self::insert_earning_points($myid,$pointsredeemed, $categorylevelrewardpercents, $date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $categorylevelrewardpercents,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        }
                        break;
                    case '3':
                        if ($global_reward_type == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                    self::insert_earning_points($myid,$pointsredeemed, $global_rewardpointss, $date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $global_rewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                self::insert_earning_points($myid,$pointsredeemed, $global_rewardpointss, $date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $global_rewardpointss,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                    self::insert_earning_points($referreduser,$pointsredeemed, $global_rewardpercents, $date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                    $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                    self::record_the_points($myid,$pointsredeemed, $global_rewardpercents,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                }
                            }else{
                                $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                self::insert_earning_points($referreduser,$pointsredeemed, $global_rewardpercents, $date,'RVPFPPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                self::record_the_points($myid,$pointsredeemed, $global_rewardpercents,$date,'RVPFPPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                            }                            
                        }
                        break;
                }
            }
        }
    }

    /*
     * @ updates earning points for user in db
     * 
     */

    public static function update_earning_points_for_user($order_id) {  
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $table_name2 = $wpdb->prefix . 'rsrecordpoints';
        global $woocommerce;        
        $termid = '';
        $order = new WC_Order($order_id);
        $fp_earned_points_sms = false;        
        do_action('rs_perform_action_for_order', $order_id);                                  
        $points_awarded_for_this_order = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'reward_points_awarded');
        $restrictuserpoints = get_option('rs_max_earning_points_for_user');
        $enabledisablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        /* Reward Points For Using Payment Gateway Method */
        if ($points_awarded_for_this_order != 'yes') {
        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$order->user_id);
        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $order->user_id);
        if($redeempoints != 0){
         $equredeemamt = self::redeeming_conversion_settings($redeempoints);
         $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
         self::record_the_points($order->user_id, '0',$redeempoints,$date,'RP','0',$equredeemamt,$order_id,'','','','',$totalpoints,'','0');                   
        }    
        $banning_type = FPRewardSystem::check_banning_type($order->user_id);
        if ($banning_type != 'earningonly' && $banning_type != 'both') {
            $checkredeeming = self::check_redeeming_in_order($order_id,$order->user_id);
            $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
            if($enableoption == 'yes'){
             if($checkredeeming == false){                   
                $getpaymentgatewayused = RSMemberFunction::user_role_based_reward_points($order->user_id, get_option('rs_reward_payment_gateways_' . $order->payment_method));
                if ($getpaymentgatewayused != '') {
                    if($enabledisablemaxpoints == 'yes'){
                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                            if($getoldpoints <= $restrictuserpoints){
                                $totalpointss = $getoldpoints + $getpaymentgatewayused;
                                if($totalpointss <= $restrictuserpoints){
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed, $date,'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                                }else{
                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                    RSPointExpiry::insert_earning_points($order->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                    
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                    RSPointExpiry::record_the_points($order->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,'0','0','0','',$totalpoints,'','0');                       
                                    
                                }
                            }else{
                                RSPointExpiry::insert_earning_points($order->user_id,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                RSPointExpiry::record_the_points($order->user_id, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                            }
                        }else{
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed, $date,'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                        }
                    }else{
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed, 'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                    }                    

                }            
             }
            }else{
                $getpaymentgatewayused = RSMemberFunction::user_role_based_reward_points($order->user_id, get_option('rs_reward_payment_gateways_' . $order->payment_method));
                if ($getpaymentgatewayused != '') {                     
                    if($enabledisablemaxpoints == 'yes'){
                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                            if($getoldpoints <= $restrictuserpoints){
                                $totalpointss = $getoldpoints + $getpaymentgatewayused;
                                if($totalpointss <= $restrictuserpoints){
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed, $date,'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                                }else{
                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                    RSPointExpiry::insert_earning_points($order->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                    
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                    RSPointExpiry::record_the_points($order->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,'0','0','0','',$totalpoints,'','0');                       
                                    
                                }
                            }else{
                                RSPointExpiry::insert_earning_points($order->user_id,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                RSPointExpiry::record_the_points($order->user_id, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                            }
                        }else{
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed,$date, 'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                        }
                    }else{
                                        $equearnamt = self::earning_conversion_settings($getpaymentgatewayused);    
                                        self::insert_earning_points($order->user_id, $getpaymentgatewayused,$pointsredeemed, $date,'RPG',$order_id,'0','0','');                                
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($order->user_id);
                                        self::record_the_points($order->user_id, $getpaymentgatewayused,'0',$date,'RPG',$equearnamt,'0',$order_id,'0','0','0','',$totalpoints,'','0');                                                
                                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                                        $totalredeempoints = '0';               
                                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                
                                        
                    }

                }
            }                        
        } 
        do_action('fp_reward_point_for_using_gateways');
        }

        /* Reward Points For Purchasing the Product */        
        if ($points_awarded_for_this_order != 'yes') {                        
            foreach ($order->get_items() as $item) {
                $banning_type = FPRewardSystem::check_banning_type($order->user_id);
                if ($banning_type != 'earningonly' && $banning_type != 'both') {
                    $productid = $item['product_id'];
                    $variationid = $item['variation_id'] == '0' || '' ? '0' : $item['variation_id'];
                    $itemquantity = $item['qty'];
                    $orderuserid = $order->user_id;                    
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                        }
                    }
                    //For Inserting Reward Points                    
                    $checked_level_for_reward_points = self::check_level_of_enable_reward_point($productid, $variationid, $termid);
                    $equearnamt = '';
                    $equredeemamt = '';
                    self::rs_insert_the_selected_level_in_reward_points($pointsredeemed,$checked_level_for_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$equearnamt,$equredeemamt,$order_id);                    
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints =($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;                                  
                    $totalredeempoints = ($redeempoints!=null)?$redeempoints:0;
                    $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints WHERE orderid = $order_id");                    
                   $wpdb->query("UPDATE $table_name SET totalredeempoints = $totalredeempoints WHERE orderid = $order_id");

                    $referreduser = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, '_referrer_name');
                    if ($referreduser != '') {
                        //For Inserting Referral Reward Points
                        $checked_level_for_referral_reward_points = self::check_level_of_enable_referral_reward_point($productid, $variationid, $termid);
                        self::rs_insert_the_selected_level_in_referral_reward_points($pointsredeemed,$referreduser,$equearnamt,$equredeemamt,$checked_level_for_referral_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,'');                                        
                        $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                        $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                        $totalredeempoints = ($redeempoints!=null)?$redeempoints:0;
                        $equredeemamt = self::redeeming_conversion_settings($totalredeempoints);
                        $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints WHERE orderid = $order_id");                    
                        $wpdb->query("UPDATE $table_name SET totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                    
                    } else {
                        $referrer_id = RSFunctionForManualReferralLink::rs_perform_manual_link_referer($order->user_id);
                        if ($referrer_id != false) {
                            $checked_level_for_referral_reward_points = self::check_level_of_enable_referral_reward_point($productid, $variationid, $termid);
                            self::rs_insert_the_reward_points_for_manuall_referrer($pointsredeemed,$referrer_id,$equearnamt,$equredeemamt,$checked_level_for_referral_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,'');                    
                            $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                            $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                            $totalredeempoints = $redeempoints;
                            $equredeemamt = self::redeeming_conversion_settings($totalredeempoints);
                            $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                      
                        }                        
                    }
                    
                    RSFunctionForCouponRewardPoints::apply_coupon_code_reward_points_user($order_id);
                    RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                    RSFunctionForEmailTemplate::rsmail_sending_on_custom_rule($orderuserid);    
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                    if($totalearnedpoints != 0 && $totalearnedpoints != ''){
                      if (get_option('rs_send_sms_earning_points') == 'yes') {
                            $fp_earned_points_sms = true;
                      }  
                    }
                    if ($fp_earned_points_sms == true) {
                        if (get_option('rs_enable_send_sms_to_user') == 'yes') {
                            if (get_option('rs_send_sms_earning_points') == 'yes') {
                                if (get_option('rs_sms_sending_api_option') == '1') {
                                    RSFunctionForSms::send_sms_twilio_api($order_id);
                                } else {
                                    RSFunctionForSms::send_sms_nexmo_api($order_id);
                                }
                            }
                        }
                    }
                }                                
            }
            update_user_meta($order->user_id, 'rsfirsttime_redeemed', 1);
            $return = self::check_weather_the_points_is_awarded_for_order($order_id);
            if (is_array($return)) {
                if (in_array(1,$return)) {
                    add_post_meta($order_id, 'reward_points_awarded', 'yes');  
                }
            }
           do_action('fp_reward_point_for_product_purchase'); 
        }
        
 $oldorderid = get_user_meta($orderuserid, 'rs_no_of_purchase_for_user',true); 
        $getorderid = (array)$order_id;        
        if($oldorderid == ''){ 
            update_user_meta($orderuserid, 'rs_no_of_purchase_for_user', $getorderid);        
        }else{            
            $mergearray = array_merge($oldorderid,(array)$getorderid); 
            update_user_meta($orderuserid, 'rs_no_of_purchase_for_user', $mergearray); 
        }
    }
    
    public static function check_weather_the_points_is_awarded_for_order($order_id){
        $order = new WC_Order($order_id);
        foreach ($order->get_items() as $item) {
            $termid = '';
            $productid = $item['product_id'];
            $variationid = $item['variation_id'] == '0' || '' ? '0' : $item['variation_id'];
            $term = get_the_terms($productid, 'product_cat');
            if (is_array($term)) {
                foreach ($term as $terms) {
                    $termid = $terms->term_id;
                }
            }
            $checked_level_for_reward_points = self::check_reward_point_for_order($productid, $variationid, $termid);            
            $array[] = $checked_level_for_reward_points;
        }  
        return $array;
    }
    
    public static function check_reward_point_for_order($productid, $variationid, $termid) {
        global $post;
        
        //Product Level
        $productlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystemcheckboxvalue') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_enable_reward_points');
        $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_reward_rule');
        $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_points');
        $productlevelrewardpercent = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        
        //Category Level
        $categorylist = wp_get_post_terms($productid, 'product_cat');
        $getcount = count($categorylist);
        $categorylevel = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_reward_system_category');
        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
        $categorylevelrewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
        $categorylevelrewardpercent = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
        
        //Global Level
        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
        $global_reward_type = get_option('rs_global_reward_type');
        $global_rewardpoints = get_option('rs_global_reward_points');
        $global_rewardpercent = get_option('rs_global_reward_percent');
        
        if (($productlevel == 'yes') || ($productlevel == '1')) {                        
            if ($productlevelrewardtype == '1') {                
                if ($productlevelrewardpoints != '') {
                    return '1';
                } else {                    
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                
                                if ($categorylevelrewardpoints != '') {
                                    return '1';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '1';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '1';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '1';
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                    
                                    if ($global_rewardpoints != '') {
                                        return '1';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '1';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '1';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '1';
                                }
                            }
                        }
                    }
                }
            } else {                
                if ($productlevelrewardpercent != '') {
                    return '1';
                } else {                    
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                                                
                                if ($categorylevelrewardpoints != '') {
                                    return '1';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '1';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '1';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '1';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '1';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '1';
                                            }
                                        }
                                    }
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                
                                    if ($global_rewardpoints != '') {
                                        return '1';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '1';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '1';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '1';
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return '0';
        }
    }

    public static function check_redeeming_in_order($order_id,$orderuserid){
        $order = new WC_Order($order_id);
        if(get_post_meta($order_id,'fp_rs_redeemed_points_value_for_revision',true) != 'yes'){
            if(isset(WC()->session)){
                $redeem_amount = WC()->session->get('fp_rs_redeem_amount') != NULL ? WC()->session->get('fp_rs_redeem_amount'):'0';
                update_post_meta($order_id,'fp_rs_redeemed_points_value',$redeem_amount);                
                WC()->session->__unset('fp_rs_redeem_amount');
            }
            add_post_meta($order_id,'fp_rs_redeemed_points_value_for_revision','yes');
        }
        $points_awarded_for_this_order = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'reward_points_awarded');
        if($points_awarded_for_this_order != 'yes'){
            $rewardpointscoupons = $order->get_items(array('coupon'));
            $getuserdatabyid = get_user_by('id', $orderuserid);        
            $getusernickname = isset($getuserdatabyid->user_login)?$getuserdatabyid->user_login:"";
            $maincouponchecker = 'sumo_' . strtolower($getusernickname);
            foreach($rewardpointscoupons as $array){
                if(in_array($maincouponchecker, $array)){                
    //                WC()->session->unset('fp_rs_redeem_amount');
                    return true;  
                }else{
                    return false;
                }
            }
        }
        $currentuserid = get_current_user_id(); 
        $getpostvalue = get_user_meta($currentuserid,'rs_selected_nominee',true);                        
        update_user_meta($currentuserid,'rs_selected_nominee',$getpostvalue);
    }


    /* Function For Checking in Which level Reward points is Awarded */

    public static function check_level_of_enable_reward_point($productid, $variationid, $termid) {
        global $post;
        
        //Product Level
        $productlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystemcheckboxvalue') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_enable_reward_points');
        $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_reward_rule');
        $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_points');
        $productlevelrewardpercent = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        
        //Category Level
        $categorylist = wp_get_post_terms($productid, 'product_cat');
        $getcount = count($categorylist);
        $categorylevel = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_reward_system_category');
        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
        $categorylevelrewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
        $categorylevelrewardpercent = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
        
        //Global Level
        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
        $global_reward_type = get_option('rs_global_reward_type');
        $global_rewardpoints = get_option('rs_global_reward_points');
        $global_rewardpercent = get_option('rs_global_reward_percent');
        
        if (($productlevel == 'yes') || ($productlevel == '1')) {                        
            if ($productlevelrewardtype == '1') {                
                if ($productlevelrewardpoints != '') {
                    return '1';
                } else {                    
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                
                                if ($categorylevelrewardpoints != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '2';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '2';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '2';
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                    
                                    if ($global_rewardpoints != '') {
                                        return '2';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '2';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '3';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '3';
                                }
                            }
                        }
                    }
                }
            } else {                
                if ($productlevelrewardpercent != '') {
                    return '1';
                } else {                    
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                                                
                                if ($categorylevelrewardpoints != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '3';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '3';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '3';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '3';
                                            }
                                        }
                                    }
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                
                                    if ($global_rewardpoints != '') {
                                        return '3';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '3';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '3';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '3';
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return '0';
        }
    }

    /* Function to insert the earned reward points to db */

    public static function rs_insert_the_selected_level_in_reward_points($pointsredeemed,$level, $productid, $variationid, $itemquantity, $orderuserid, $termid,$equearnamt,$equredeemamt,$order_id) {
        
        $variable_product1 = new WC_Product_Variation($variationid);
        if (get_option('rs_set_price_percentage_reward_points') == '1') {
            $variationregularprice = $variable_product1->regular_price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_regular_price') : $variationregularprice;
        } else {
            $variationregularprice = $variable_product1->price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_price') : $variationregularprice;
        }

        do_action_ref_array('rs_update_points_for_referral_simple', array(&$getregularprice, &$item));
        
        do_action_ref_array('rs_update_points_for_variable', array(&$getregularprice, &$item)); 
        
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        
        $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_reward_rule');
        $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_points');
        $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        $pointforconversion = get_option('rs_earn_point');
        $pointforconversionvalue = get_option('rs_earn_point_value');
        $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_reward_percent');
        $rewardpercentpl = $rewardpercentforproductlevel / 100;
        $getaveragepoints = $rewardpercentpl * $getregularprice;
        $pointswithvalue = $getaveragepoints * $pointforconversion;
        $productlevelrewardpercent = $pointswithvalue / $pointforconversionvalue;
        
        if (($productid != '') || ($variationid != '') || ($variationid != '0')) {
            $rewardpoints = array('0');
            $rewardpercent = array('0');
            $categorylist = wp_get_post_terms($productid, 'product_cat');
            $getcount = count($categorylist);
            $term = get_the_terms($productid, 'product_cat');
            if (is_array($term)) {
                foreach ($term as $terms) {
                    $termid = $terms->term_id;                            
            if($getcount > 1){                
                    $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
                    $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
                    if($rewardpointsforcategory == ''){
                        $rewardpoints[] = get_option('rs_global_reward_points');
                    }else{
                        $rewardpoints[] = $rewardpointsforcategory;
                    }
                    $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
                    if($rewardpercentforcategory == ''){
                        $pointforconversion = get_option('rs_earn_point');
                        $pointforconversionvalue = get_option('rs_earn_point_value');
                        $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
                        $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                        $pointswithvalue = $getaveragepoints * $pointforconversion;
                        $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;                        
                    }else{
                        $pointforconversion = get_option('rs_earn_point');
                        $pointforconversionvalue = get_option('rs_earn_point_value');
                        $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent') / 100;
                        $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                        $pointswithvalue = $getaveragepoints * $pointforconversion;
                        $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;                        
                    }                                
            }else {            
            $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_rs_rule');
            $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_points');
            if($rewardpointsforcategory == ''){
                 $rewardpoints[] = get_option('rs_global_reward_points');
            }else{
                 $rewardpoints[] = $rewardpointsforcategory;
            }
            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent');
            if($rewardpercentforcategory == ''){
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
            }else{
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'rs_category_percent') / 100;
                $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;   
            }                        
            }
                }
            }
            $categorylevelrewardpoints = max($rewardpoints);
            $categorylevelrewardpercent = max($rewardpercent);
        }
        $global_reward_type = get_option('rs_global_reward_type');
        $global_rewardpoints = get_option('rs_global_reward_points');          
        $pointforconversion = get_option('rs_earn_point');
        $pointforconversionvalue = get_option('rs_earn_point_value');
        $get_global_rewardpercent = get_option('rs_global_reward_percent') / 100;
        $getaveragepoints = $get_global_rewardpercent * $getregularprice;
        $pointswithvalue = $getaveragepoints * $pointforconversion;
        $global_rewardpercent = $pointswithvalue / $pointforconversionvalue; 
        $totalearnedpoints = '0';
        $totalredeempoints = '0';        
        $refuserid = '';
        $refuserid = $refuserid != '' ? $refuserid : 0;
        $checkredeeming = self::check_redeeming_in_order($order_id,$orderuserid);
        $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
        $restrictuserpoints = get_option('rs_max_earning_points_for_user');
        $enabledisablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');
        $getnomineeid = get_user_meta(get_current_user_id(),'rs_selected_nominee',true);
        
        include 'rs_insert_points_for_product_purchase.php';
    }

    /* Function For Checking in Which level Reward points is Awarded */

    public static function check_level_of_enable_referral_reward_point($productid, $variationid, $termid) {
        global $post;
        //Product Level
        $productlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystemcheckboxvalue') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_enable_reward_points');
        $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referral_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_referral_reward_rule');
        $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_points');
        $productlevelrewardpercent = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
        
        //Category Level
        $categorylist = wp_get_post_terms($productid, 'product_cat');
        $getcount = count($categorylist);
        $categorylevel = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'enable_reward_system_category');
        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
        $categorylevelrewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
        $categorylevelrewardpercent = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
        
        //Global Level
        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
        $global_reward_type = get_option('rs_global_referral_reward_type');
        $global_rewardpoints = get_option('rs_global_referral_reward_point');
        $global_rewardpercent = get_option('rs_global_referral_reward_percent');
        
        if (($productlevel == 'yes') || ($productlevel == '1')) {            
            if ($productlevelrewardtype == '1') {                
                if ($productlevelrewardpoints != '') {
                    return '1';
                } else {                   
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                
                                if ($categorylevelrewardpoints != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '2';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '2';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '2';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '2';
                                            }
                                        }
                                    }
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                   
                                    if ($global_rewardpoints != '') {
                                        return '2';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '2';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '3';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '3';
                                }
                            }
                        }
                    }
                }
            } else {                
                if ($productlevelrewardpercent != '') {
                    return '1';
                } else {                    
                    if ($getcount >= '1') {                        
                        if (($categorylevel == 'yes') || ($categorylevel != '')) {                            
                            if (($categorylevelrewardtype == '1')) {                                
                                if ($categorylevelrewardpoints != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '3';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '3';
                                            }
                                        }
                                    }
                                }
                            } else {                                
                                if ($categorylevelrewardpercent != '') {
                                    return '2';
                                } else {                                    
                                    if ($global_enable == '1') {                                        
                                        if ($global_reward_type == '1') {                                            
                                            if ($global_rewardpoints != '') {
                                                return '3';
                                            }
                                        } else {                                            
                                            if ($global_rewardpercent != '') {
                                                return '3';
                                            }
                                        }
                                    }
                                }
                            }
                        } else {                            
                            if ($global_enable == '1') {                                
                                if ($global_reward_type == '1') {                                    
                                    if ($global_rewardpoints != '') {
                                        return '3';
                                    }
                                } else {                                    
                                    if ($global_rewardpercent != '') {
                                        return '3';
                                    }
                                }
                            }
                        }
                    } else {                        
                        if ($global_enable == '1') {                            
                            if ($global_reward_type == '1') {                                
                                if ($global_rewardpoints != '') {
                                    return '3';
                                }
                            } else {                                
                                if ($global_rewardpercent != '') {
                                    return '3';
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return '0';
        }
    }

    /* Function to insert referral reward points into db */

    public static function rs_insert_the_selected_level_in_referral_reward_points($pointsredeemed,$referreduser,$equearnamt,$equredeemamt,$level, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail) {
        
        $variable_product1 = new WC_Product_Variation($variationid);
        if (get_option('rs_set_price_percentage_reward_points') == '1') {
            $variationregularprice = $variable_product1->regular_price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_regular_price') : $variationregularprice;
        } else {
            $variationregularprice = $variable_product1->price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_price') : $variationregularprice;
        }

        do_action_ref_array('rs_update_points_for_referral_simple', array(&$getregularprice, &$item));
        
        do_action_ref_array('rs_update_points_for_variable', array(&$getregularprice, &$item));
        
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        $user_info = new WP_User($orderuserid);
        $registered_date = $user_info->user_registered;
        $limitation = false;
        $modified_registered_date = date('Y-m-d h:i:sa', strtotime($registered_date));
        $delay_days = get_option('_rs_select_referral_points_referee_time_content');
        $checking_date = date('Y-m-d h:i:sa', strtotime($modified_registered_date . ' + ' . $delay_days . ' days '));
        $modified_checking_date = strtotime($checking_date);
        $current_date = date('Y-m-d h:i:sa');
        $modified_current_date = strtotime($current_date);
        //Is for Immediatly
        if (get_option('_rs_select_referral_points_referee_time') == '1') {
            $limitation = true;
        } else {
            // Is for Limited Time with Number of Days
            if ($modified_current_date > $modified_checking_date) {
                $limitation = true;
            } else {
                $limitation = false;
            }
        }

        if ($limitation == true) {
            $refuser = get_user_by('login', $referreduser);
            if ($refuser != false) {
                $myid = $refuser->ID;
            } else {
                $myid = $referreduser;
            }
            $banning_type = FPRewardSystem::check_banning_type($myid);
            if ($banning_type != 'earningonly' && $banning_type != 'both') {
                $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referral_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_referral_reward_rule');
                $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_points');                                
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $rewardpercentpl = $rewardpercentforproductlevel / 100;
                $getaveragepoints = $rewardpercentpl * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $productlevelrewardpercent = $pointswithvalue / $pointforconversionvalue;

        
                if (($productid != '') || ($variationid != '') || ($variationid != '0')) {
                    $rewardpoints = array('0');
                    $rewardpercent = array('0');
                    $categorylist = wp_get_post_terms($productid, 'product_cat');
                    $getcount = count($categorylist);
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                    if ($getcount > 1) {                                                   
                            $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                            $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                            if ($rewardpointsforcategory == '') {
                                $rewardpoints[] = get_option('rs_global_referral_reward_point');
                            } else {
                                $rewardpoints[] = $rewardpointsforcategory;
                            }
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                            if ($rewardpercentforcategory == '') {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            } else {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                                $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            }                                                
                    } else {                        
                        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                        $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                        if ($rewardpointsforcategory == '') {
                            $rewardpoints[] = get_option('rs_global_referral_reward_point');
                        } else {
                            $rewardpoints[] = $rewardpointsforcategory;
                        }
                        $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                        if($rewardpercentforcategory == ''){
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                            $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }else {
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                            $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }                         
                    }
                    
                        }
                    }
                    $categorylevelrewardpoints = max($rewardpoints);
                    $categorylevelrewardpercent = max($rewardpercent);
                }
                $global_reward_type = get_option('rs_global_referral_reward_type');
                $global_rewardpoints = get_option('rs_global_referral_reward_point');                               
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $global_rewardpercent = $pointswithvalue / $pointforconversionvalue; 
                
                $checkredeeming = self::check_redeeming_in_order($order_id,$orderuserid);
                $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
                $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                $enabledisablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');
                switch ($level) {
                    case '1':
                        if ($productlevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        }
                        break;
                    case '2':
                        if ($categorylevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        }
                        break;
                    case '3':
                        if ($global_reward_type == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                               if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {                           
                                if($enableoption == 'yes'){
                                    if($checkredeeming == true){
                                        if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                        
                                    }                                        
                                    }
                                }else{
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed, $date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0', $date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0', $date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed,  $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                        
                                    }
                                }                                                                                       
                        }
                        break;
                }
            }
        }
    }
    
    
    
    public static function rs_insert_the_reward_points_for_manuall_referrer($pointsredeemed,$referrer_id,$equearnamt,$equredeemamt,$level, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail) {
        
        $variable_product1 = new WC_Product_Variation($variationid);
        if (get_option('rs_set_price_percentage_reward_points') == '1') {
            $variationregularprice = $variable_product1->regular_price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_regular_price') : $variationregularprice;
        } else {
            $variationregularprice = $variable_product1->price;
            $getregularprice = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_price') : $variationregularprice;
        }

        do_action_ref_array('rs_update_points_for_referral_simple', array(&$getregularprice, &$item));
        
        do_action_ref_array('rs_update_points_for_variable', array(&$getregularprice, &$item));
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        $user_info = new WP_User($orderuserid);
        $registered_date = $user_info->user_registered;
        $limitation = false;
        $modified_registered_date = date('Y-m-d h:i:sa', strtotime($registered_date));
        $delay_days = get_option('_rs_select_referral_points_referee_time_content');
        $checking_date = date('Y-m-d h:i:sa', strtotime($modified_registered_date . ' + ' . $delay_days . ' days '));
        $modified_checking_date = strtotime($checking_date);
        $current_date = date('Y-m-d h:i:sa');
        $modified_current_date = strtotime($current_date);
        //Is for Immediatly
        if (get_option('_rs_select_referral_points_referee_time') == '1') {
            $limitation = true;
        } else {
            // Is for Limited Time with Number of Days
            if ($modified_current_date > $modified_checking_date) {
                $limitation = true;
            } else {
                $limitation = false;
            }
        }

        if ($limitation == true) {          
            
            $myid = $referrer_id;
            
            $banning_type = FPRewardSystem::check_banning_type($myid);
            if ($banning_type != 'earningonly' && $banning_type != 'both') {
                $productlevelrewardtype = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referral_rewardsystem_options') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_select_referral_reward_rule');
                $productlevelrewardpoints = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempoints') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_points');                                
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $rewardpercentforproductlevel = $variationid == '0' || '' ? RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_referralrewardsystempercent') : RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variationid, '_referral_reward_percent');
                $rewardpercentpl = $rewardpercentforproductlevel / 100;
                $getaveragepoints = $rewardpercentpl * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $productlevelrewardpercent = $pointswithvalue / $pointforconversionvalue;

        
                if (($productid != '') || ($variationid != '') || ($variationid != '0')) {
                    $rewardpoints = array('0');
                    $rewardpercent = array('0');
                    $categorylist = wp_get_post_terms($productid, 'product_cat');
                    $getcount = count($categorylist);
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                    if ($getcount > 1) {                                                   
                            $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                            $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                            if ($rewardpointsforcategory == '') {
                                $rewardpoints[] = get_option('rs_global_referral_reward_point');
                            } else {
                                $rewardpoints[] = $rewardpointsforcategory;
                            }
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                            if ($rewardpercentforcategory == '') {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            } else {
                                $pointforconversion = get_option('rs_earn_point');
                                $pointforconversionvalue = get_option('rs_earn_point_value');
                                $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                                $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointforconversion;
                                $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                            }                                                
                    } else {                        
                        $categorylevelrewardtype = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_enable_rs_rule');
                        $rewardpointsforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_points');
                        if ($rewardpointsforcategory == '') {
                            $rewardpoints[] = get_option('rs_global_referral_reward_point');
                        } else {
                            $rewardpoints[] = $rewardpointsforcategory;
                        }
                        $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent');
                        if($rewardpercentforcategory == ''){
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                            $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }else {
                            $pointforconversion = get_option('rs_earn_point');
                            $pointforconversionvalue = get_option('rs_earn_point_value');
                            $rewardpercentforcategory = RSFunctionForSavingMetaValues::rewardsystem_get_woocommerce_term_meta($termid, 'referral_rs_category_percent') / 100;
                            $getaveragepoints = $rewardpercentforcategory * $getregularprice;
                            $pointswithvalue = $getaveragepoints * $pointforconversion;
                            $rewardpercent[] = $pointswithvalue / $pointforconversionvalue;
                        }                         
                    }
                    
                        }
                    }
                    $categorylevelrewardpoints = max($rewardpoints);
                    $categorylevelrewardpercent = max($rewardpercent);
                }
                $global_reward_type = get_option('rs_global_referral_reward_type');
                $global_rewardpoints = get_option('rs_global_referral_reward_point');                               
                $pointforconversion = get_option('rs_earn_point');
                $pointforconversionvalue = get_option('rs_earn_point_value');
                $get_global_rewardpercent = get_option('rs_global_referral_reward_percent') / 100;
                $getaveragepoints = $get_global_rewardpercent * $getregularprice;
                $pointswithvalue = $getaveragepoints * $pointforconversion;
                $global_rewardpercent = $pointswithvalue / $pointforconversionvalue;       
                
                
                $checkredeeming = self::check_redeeming_in_order($order_id,$orderuserid);
                $enableoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, 'rs_check_enable_option_for_redeeming');
                $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                $enabledisablemaxpoints = get_option('rs_enable_disable_max_earning_points_for_user');
                switch ($level) {
                    case '1':
                        if ($productlevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $productlevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $productlevelrewardpercentss = RSMemberFunction::user_role_based_reward_points($orderuserid, $productlevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $productlevelrewardpercentss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($productlevelrewardpercentss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $productlevelrewardpercentss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $productlevelrewardpercentss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        }
                        break;
                    case '2':
                        if ($categorylevelrewardtype == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                                if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $categorylevelrewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $categorylevelrewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $categorylevelrewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($categorylevelrewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $categorylevelrewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $categorylevelrewardpercents, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        }
                        break;
                    case '3':
                        if ($global_reward_type == '1') {
                            if($enableoption == 'yes'){
                                if($checkredeeming == false){
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                        
                                    }                                    
                                }
                            }else{
                               if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpoints;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpointss = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpoints) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpointss,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpointss);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpointss,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpointss, array_filter((array) $previouslog));
                                        
                                    }
                            }                            
                        } else {                           
                                if($enableoption == 'yes'){
                                    if($checkredeeming == true){
                                        if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                        
                                    }                                        
                                    }
                                }else{
                                    if($enabledisablemaxpoints == 'yes'){
                                        if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                            $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);                            
                                            if($getoldpoints <= $restrictuserpoints){
                                                $totalpointss = $getoldpoints + $global_rewardpercent;
                                                if($totalpointss <= $restrictuserpoints){
                                                    $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                                    self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                                    $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                                    $previouslog = get_option('rs_referral_log');
                                                    RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                                    
                                                }else{
                                                    $insertpoints = $restrictuserpoints - $getoldpoints;
                                                    RSPointExpiry::insert_earning_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                                    $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                                    $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);                                                    
                                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                    RSPointExpiry::record_the_points($myid, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                                    
                                                }                                
                                            }else{
                                                RSPointExpiry::insert_earning_points($myid,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                                $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                                RSPointExpiry::record_the_points($myid, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                            }
                                        }else{
                                            $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                            self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed,$date, 'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                            $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                            self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                            $previouslog = get_option('rs_referral_log');
                                            RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                            
                                        }
                                    }else{
                                        $global_rewardpercents = RSMemberFunction::user_role_based_reward_points($orderuserid, $global_rewardpercent) * $itemquantity;                            
                                        self::insert_earning_points($myid, $global_rewardpercents,$pointsredeemed, $date,'PPRRP',$order_id,$totalearnedpoints,$totalredeempoints,$reasonindetail);
                                        $equearnamt = self::earning_conversion_settings($global_rewardpercents);
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($myid);
                                        self::record_the_points($myid, $global_rewardpercents,$pointsredeemed,$date,'PPRRP',$equearnamt,$equredeemamt,$order_id,$productid,$variationid,$orderuserid,'',$totalpoints,'','0');
                                        $previouslog = get_option('rs_referral_log');
                                        RS_Referral_Log::main_referral_log_function($myid, $orderuserid, $global_rewardpercents, array_filter((array) $previouslog));
                                        
                                    }
                                }                                                                                       
                        }
                        break;
                }
            }
        }
    }
    
    public static function delete_referral_registered_people($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $table_name2 = $wpdb->prefix . 'rsrecordpoints';        
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        $registration_points = get_option('rs_reward_signup');
                
        $referral_registration_points = RSMemberFunction::user_role_based_reward_points($user_id, get_option('rs_referral_reward_signup'));
        $getreferredusermeta = get_user_meta($user_id, '_rs_i_referred_by', true);
        $refuserid = $getreferredusermeta;
        $getregisteredcount = get_user_meta($refuserid, 'rsreferreduserregisteredcount', true);
        $currentregistration = $getregisteredcount - 1;
        update_user_meta($refuserid, 'rsreferreduserregisteredcount', $currentregistration);
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        /*
         * Update the Referred Person Registration Count End
         */

        /* Below Code is for Removing Referral Point Registration when Deleting User */
        if ($getreferredusermeta != '') {
            $oldpointss = self::get_sum_of_total_earned_points($refuserid);
            $currentregistrationpointss = $oldpointss - $referral_registration_points;
            self::insert_earning_points($refuserid, '0',$referral_registration_points,$date, 'RVPFRRRP','0','0','0','');
            $equredeemamt = self::redeeming_conversion_settings($referral_registration_points);
            $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($refuserid);
            self::record_the_points($refuserid,'0',$referral_registration_points,$date,'RVPFRRRP','0',$equredeemamt,'0','0','0',$user_id,'',$totalpoints,'','0');                                
            update_user_meta($user_id, '_rs_i_referred_by', $refuserid);
        }
        $getlistoforder = get_user_meta($user_id, '_update_user_order', true);        
        if (is_array($getlistoforder)) {            
            foreach ($getlistoforder as $order_id) {                
                $order = new WC_Order($order_id);
                if ($order->status == 'completed') {                    
                    $pointslog = array();
                    $usernickname = get_user_meta($order->user_id, 'nickname', true);                                        
                    foreach ($order->get_items() as $item) {
                        if (get_option('rs_set_price_percentage_reward_points') == '1') {
                            $getregularprice = get_post_meta($item['product_id'], '_regular_price', true);
                        } else {
                            $getregularprice = get_post_meta($item['product_id'], '_price', true);
                        }

                    do_action_ref_array('rs_delete_points_for_referral_simple', array(&$getregularprice, &$item));
                        
                    $productid = $item['product_id'];
                    $variationid = $item['variation_id'] == '0' || '' ? '0' : $item['variation_id'];
                    $itemquantity = $item['qty'];
                    $orderuserid = $order->user_id;                    
                    $term = get_the_terms($productid, 'product_cat');
                    if (is_array($term)) {
                        foreach ($term as $terms) {
                            $termid = $terms->term_id;
                        }
                    }
                    

                    $referreduser = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($order_id, '_referrer_name');
                    if ($referreduser != '') {
                    //For Inserting Referral Reward Points
                    $checked_level_for_referral_reward_points = self::check_level_of_enable_referral_reward_point($productid, $variationid, $termid);
                    self::rs_insert_the_selected_level_revised_referral_reward_points('0',$referreduser,'0','0',$checked_level_for_referral_reward_points, $productid, $variationid, $itemquantity, $orderuserid, $termid,$order_id,'0','0','');                    
                    $gettotalearnedpoints = $wpdb->get_results("SELECT SUM((earnedpoints)) as availablepoints FROM $table_name WHERE orderid = $order_id", ARRAY_A);
                    $totalearnedpoints = ($gettotalearnedpoints[0]['availablepoints'] != NULL)?$gettotalearnedpoints[0]['availablepoints']:0;
                    $totalredeempoints = '0';
                    $equredeemamt = self::redeeming_conversion_settings($totalredeempoints);
                    $wpdb->query("UPDATE $table_name SET totalearnedpoints = $totalearnedpoints,totalredeempoints = $totalredeempoints WHERE orderid = $order_id");                    

                    }
                    self::update_revised_reward_points_to_user($order_id,$orderuserid);  
                   }
               }
            }
        }
    }    

    /*
     * 
     * @ Redeeming Conversion settings
     * @returns equivalent currency  value for current points
     */

    public static function redeeming_conversion_settings($points_to_redeem) {
        $user_entered_points = $points_to_redeem; //Ex:10points
        $conversion_rate_points = get_option('rs_redeem_point'); //Conversion Points
        $conversion_rate_points_value = get_option('rs_redeem_point_value'); //Value for the Conversion Points (i.e)  1 points is equal to $.2
        $conversion_step1 = $user_entered_points / $conversion_rate_points; //Ex: 10/1=10
        $converted_value = $conversion_step1 * $conversion_rate_points_value; //Ex:10 * 2 = 20
        return $converted_value; // $.20        
    }   
    
    /*
     * 
     * @ Earning Conversion settings
     * @returns equivalent currency  value for current points
     */

    public static function earning_conversion_settings($earnpoints) {
        $user_entered_points = $earnpoints; //Ex:10points
        $conversion_rate_points = get_option('rs_earn_point'); //Conversion Points
        $conversion_rate_points_value = get_option('rs_earn_point_value'); //Value for the Conversion Points (i.e)  1 points is equal to $.2
        $conversion_step1 = $user_entered_points / $conversion_rate_points; //Ex: 10/1=10
        $converted_value = $conversion_step1 * $conversion_rate_points_value; //Ex:10 * 2 = 20
        return $converted_value; // $.20        
    }

    public static function get_reviewed_user_list($commentid, $approved) {
        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $post;
        $mycomment = get_comment($commentid);        
        $get_comment_post_type = get_post_type($mycomment->comment_post_ID);
        $orderuserid = $mycomment->user_id;
        $noofdays = get_option('rs_point_to_be_expire');
                    if(($noofdays != '0')&& ($noofdays != '')){
                        $date = time() + ($noofdays * 24 * 60 * 60);   
                    }else{
                        $date = '999999999999';
                    }
        if ($get_comment_post_type == 'product') {            
            if (get_option('rs_restrict_reward_product_review') == 'yes') {
                $getuserreview = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID);
                if ($getuserreview != '1') {
                    if (($approved == true)) {
                        $getreviewpoints = RSMemberFunction::user_role_based_reward_points($mycomment->user_id, get_option("rs_reward_product_review"));
                        $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                        $enabledisablemaximumpoints = get_option('rs_enable_disable_max_earning_points_for_user');
                        $currentregistrationpoints = $getreviewpoints;
                        if ($enabledisablemaximumpoints == 'yes') {
                            if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);                            
                                if($getoldpoints <= $restrictuserpoints){
                                    $totalpointss = $getoldpoints + $currentregistrationpoints;
                                    if($totalpointss <= $restrictuserpoints){
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                                    }else{
                                        $insertpoints = $restrictuserpoints - $getoldpoints;
                                        self::insert_earning_points($mycomment->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                        $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                                        self::record_the_points($mycomment->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                    }
                                }else{
                                    RSPointExpiry::insert_earning_points($mycomment->user_id,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                                    RSPointExpiry::record_the_points($mycomment->user_id, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                }
                            }else{
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                            }                            
                        }else{
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                        }                                                                       
                    }
                }
            } else {
                if (($approved == true)) {
                    $getreviewpoints = RSMemberFunction::user_role_based_reward_points($mycomment->user_id, get_option("rs_reward_product_review"));                    
                    $restrictuserpoints = get_option('rs_max_earning_points_for_user');
                    $enabledisablemaximumpoints = get_option('rs_enable_disable_max_earning_points_for_user');
                    $currentregistrationpoints = $getreviewpoints;
                    if ($enabledisablemaximumpoints == 'yes') {
                            if(($restrictuserpoints != '') && ($restrictuserpoints != '0')){
                                $getoldpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);                            
                                if($getoldpoints <= $restrictuserpoints){
                                    $totalpointss = $getoldpoints + $currentregistrationpoints;
                                    if($totalpointss <= $restrictuserpoints){
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                                    }else{
                                        $insertpoints = $restrictuserpoints - $getoldpoints;
                                        self::insert_earning_points($mycomment->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = RSPointExpiry::earning_conversion_settings($insertpoints);
                                        $equredeemamt = RSPointExpiry::redeeming_conversion_settings($pointsredeemed);
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                                        self::record_the_points($mycomment->user_id, $insertpoints,$pointsredeemed,$date,'MREPFU',$equearnamt,$equredeemamt,$order_id,$productid,'0','0','',$totalpoints,'','0');                       
                                    }
                                }else{
                                    RSPointExpiry::insert_earning_points($mycomment->user_id,'0','0',$date,'MREPFU',$order_id,'0','0','');
                                    $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($orderuserid);
                                    RSPointExpiry::record_the_points($mycomment->user_id, '0','0',$date,'MREPFU','0','0',$order_id,'0','0','0','',$totalpoints,'','0');                    
                                }
                            }else{
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                            }                            
                        }else{
                                        $redeempoints = RSFunctionToApplyCoupon::update_redeem_reward_points_to_user($order_id,$orderuserid);
                                        $pointsredeemed = self::perform_calculation_with_expiry($redeempoints, $orderuserid);
                                        self::insert_earning_points($mycomment->user_id,$currentregistrationpoints,$pointsredeemed,$date,'RPPR',$order_id,$totalearnedpoints,$totalredeempoints,'');
                                        $equearnamt = self::earning_conversion_settings($currentregistrationpoints); 
                                        $productid = $mycomment->comment_post_ID;
                                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($mycomment->user_id);
                                        self::record_the_points($mycomment->user_id, $currentregistrationpoints,'0',$date,'RPPR',$equearnamt,'0',$order_id,$productid,$variationid,$refuserid,'',$totalpoints,'','0');                                
                                        RSFunctionForSavingMetaValues::rewardsystem_update_user_meta($mycomment->user_id, 'userreviewed' . $mycomment->comment_post_ID, '1');                        
                        }                    
                }
            }
        }
        do_action('fp_reward_point_for_product_review');
    }
    
    public static function getcommentstatus($id) {
        if (get_option('rs_review_reward_status') == '1') {
            self::get_reviewed_user_list($id, true);
        } else {
            self::get_reviewed_user_list($id, false);
        }
    }        
    
    
    public static function rs_function_to_display_log($csvmasterlog,$user_deleted,$order_status_changed,$earnpoints,$order,$checkpoints,$productid,$orderid,$variationid,$userid,$refuserid,$reasonindetail,$redeempoints,$masterlog,$nomineeid,$usernickname,$nominatedpoints){
        $getmsgrpg = '';
        $post_url = admin_url('post.php?post=' . $orderid) . '&action=edit';
        $myaccountlink = get_permalink(get_option('woocommerce_myaccount_page_id'));
        $vieworderlink = esc_url_raw(add_query_arg('view-order', $orderid, $myaccountlink));
        $vieworderlinkforfront = '<a href="' . $vieworderlink . '">#' . $orderid . '</a>';
        switch($checkpoints){
            case 'RPG' :
                $getmsgrpg = get_option('_rs_localize_reward_for_payment_gateway_message');
                $replacepaymenttitle = str_replace('{payment_title}', $order->payment_method_title, $getmsgrpg);
                return $replacepaymenttitle;
                break;
            case 'PPRP':
                if($masterlog == false){
                    $getmsgrpg = get_option('_rs_localize_points_earned_for_purchase_main');                    
                    $replaceorderid = str_replace('{currentorderid}',$vieworderlinkforfront , $getmsgrpg);
                    return $replaceorderid;
                break;
                }else{
                    if($csvmasterlog == false){
                        $getmsgrpg = get_option('_rs_localize_product_purchase_reward_points');
                        $replaceproductid = str_replace('{itemproductid}',$productid , $getmsgrpg);
                        $replaceorderid = str_replace('{currentorderid}',$vieworderlinkforfront , $replaceproductid);
                        return $replaceorderid;   
                    }else{
                        $getmsgrpg = get_option('_rs_localize_product_purchase_reward_points');
                        $replaceproductid = str_replace('{itemproductid}',$productid , $getmsgrpg);
                        $replaceorderid = str_replace('{currentorderid}','#'.$orderid , $replaceproductid);
                        return $replaceorderid;
                    }                    
                }                
            case 'PPRRP':
                $getmsgrpg = get_option('_rs_localize_referral_reward_points_for_purchase');
                $replaceproductid = str_replace('{itemproductid}',$productid , $getmsgrpg);                
                $replaceusername = str_replace('{purchasedusername}',$refuserid != '' ? $refuserid : __('Guest','rewardsystem') , $replaceproductid);
                return $replaceusername;
                break;
            case 'RRP':
                $getmsgrpg = get_option('_rs_localize_points_earned_for_registration');                
                return $getmsgrpg;
                break;
            case 'RRRP':
                $getmsgrpg = get_option('_rs_localize_points_earned_for_referral_registration');                                
                $replaceusername = str_replace('{registereduser}',$refuserid , $getmsgrpg);
                return $replaceusername;
                break;
            case 'LRP':
                $getmsgrpg = get_option('_rs_localize_reward_points_for_login');                                                
                return $getmsgrpg;
                break;
            case 'RPC':
                $getmsgrpg = get_option('_rs_localize_coupon_reward_points_log');                                                
                return $getmsgrpg;
                break;            
            case 'RPFL':
                $getmsgrpg = get_option('_rs_localize_reward_for_facebook_like');                                                
                return $getmsgrpg;
                break;
            case 'RPTT':
                $getmsgrpg = get_option('_rs_localize_reward_for_twitter_tweet');                                                
                return $getmsgrpg;
                break;
            case 'RPGPOS':
                $getmsgrpg = get_option('_rs_localize_reward_for_google_plus');                                                
                return $getmsgrpg;
                break;
            case 'RPVL':
                $getmsgrpg = get_option('_rs_localize_reward_for_vk');                                                
                return $getmsgrpg;
                break;
            case 'RPPR':
                $getmsgrpg = get_option('_rs_localize_points_earned_for_product_review');  
                $replaceproductid = str_replace('{reviewproductid}',$productid , $getmsgrpg);
                return $replaceproductid;
                break;
            case 'RP':
                if($csvmasterlog == false){
                    $getmsgrpg = get_option('_rs_localize_points_redeemed_towards_purchase');  
                    $replaceproductid = str_replace('{currentorderid}',$vieworderlinkforfront , $getmsgrpg);
                    return $replaceproductid;
                    break;
                }else{
                    $getmsgrpg = get_option('_rs_localize_points_redeemed_towards_purchase');  
                    $replaceproductid = str_replace('{currentorderid}','#'.$orderid , $getmsgrpg);
                    return $replaceproductid;
                    break;
                }
            case 'MAP':
                $getmsgrpg = $reasonindetail;                  
                return $getmsgrpg;
                break;
            case 'MRP':
                $getmsgrpg = $reasonindetail;                  
                return $getmsgrpg;
                break;  
            case 'CBRP':
                $getmsgrpg = get_option('_rs_localize_points_to_cash_log');                  
                return $getmsgrpg;
                break;
            case 'RCBRP':
                $getmsgrpg = get_option('_rs_localize_points_to_cash_log_revised');                  
                return $getmsgrpg;
                break;
            case 'RPGV':
                $getmsgrpg = get_option('_rs_localize_voucher_code_usage_log_message');
                $replaceproductid = str_replace('{rsusedvouchercode}',$reasonindetail, $getmsgrpg);
                return $replaceproductid;
                break;
            case 'RPBSRP':
                $getmsgrpg = get_option('_rs_localize_buying_reward_points_log');
                $replaceproductid = str_replace('{rsbuyiedrewardpoints}',$earnpoints, $getmsgrpg);
                return $replaceproductid;
                break;
            case 'MAURP':
                $getmsgrpg = $reasonindetail;                
                return $getmsgrpg;
                break;
            case 'MRURP':
                $getmsgrpg = $reasonindetail;                
                return $getmsgrpg;
                break;
            case 'MREPFU':
                $getmsgrpg = get_option('_rs_localize_max_earning_points_log');
                $replacepoints = get_option('rs_max_earning_points_for_user');
                $replace = str_replace('[rsmaxpoints]', $replacepoints, $getmsgrpg);
                return $replace;
                break;
            case 'RPFGW':
                $getmsgrpg = get_option('_rs_reward_points_gateway_log_localizaation');                
                return $getmsgrpg;
                break;
            case 'RVPFRPG':
                $getmsgrpg = get_option('_rs_localize_revise_reward_for_payment_gateway_message');
                $replaceproductid = str_replace('{payment_title}',$order->payment_method_title , $getmsgrpg);
                return $replaceproductid;
                break;
            case 'RVPFPPRP':
                if($masterlog == false){
                    if($csvmasterlog == false){
                        $getmsgrpg = get_option('_rs_log_revise_product_purchase_main');
                        $replaceproductid = str_replace('{currentorderid}',$vieworderlinkforfront, $getmsgrpg);
                        return $replaceproductid;
                        break;
                    }else{
                        $getmsgrpg = get_option('_rs_log_revise_product_purchase_main');
                        $replaceproductid = str_replace('{currentorderid}','#'.$orderid, $getmsgrpg);
                        return $replaceproductid;
                        break;
                    }
                }else{
                    $getmsgrpg = get_option('_rs_log_revise_product_purchase');
                    $replaceproductid = str_replace('{productid}',$productid, $getmsgrpg);
                    return $replaceproductid;
                    break;
                }
            case 'RVPFPPRRP':
                if($order_status_changed == true){
                    $getmsgrpg = get_option('_rs_log_revise_referral_product_purchase');
                    $replaceproductid = str_replace('{productid}',$productid, $getmsgrpg);
                    return $replaceproductid;
                    break;
                }elseif($user_deleted == true){
                    $getmsgrpg = get_option('_rs_localize_revise_points_for_referral_purchase');
                    $replaceproductid = str_replace('{productid}',$productid, $getmsgrpg);
                    $replaceusername = str_replace('{usernickname}',$refuserid , $replaceproductid);
                    return $replaceusername;
                    break;
                }                
            case 'RVPFRP':
                $getmsgrpg = get_option('_rs_log_revise_points_redeemed_towards_purchase');                
                return $getmsgrpg;
                break;            
            case 'RVPFRRRP':
                $getmsgrpg = get_option('_rs_localize_referral_account_signup_points_revised');                
                $replaceproductid = str_replace('{usernickname}',$refuserid, $getmsgrpg);
                return $replaceproductid;
                break;
            case 'RVPFRPVL':
                $getmsgrpg = get_option('_rs_localize_reward_for_vk_like_revised');                                
                return $getmsgrpg;
                break;
            case 'RVPFRPGPOS':
                $getmsgrpg = get_option('_rs_localize_reward_for_google_plus_revised');                                
                return $getmsgrpg;
                break;
            case 'RVPFRPFL':
                $getmsgrpg = get_option('_rs_localize_reward_for_facebook_like_revised');                                
                return $getmsgrpg;
                break;
            case 'PPRPFN': 
                if($masterlog == true){
                    $getmsgrpg = get_option('_rs_localize_log_for_nominee'); 
                    $replaceproductid = str_replace('[points]',$earnpoints, $getmsgrpg);
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $replaceproductid);
                    $replaceproductid2 = str_replace('[name]',$usernickname, $replaceproductid1);
                    return $replaceproductid2;
                    break;                                    
                }else{
                    $getmsgrpg = get_option('_rs_localize_log_for_nominee'); 
                    $replaceproductid = str_replace('[points]',$earnpoints, $getmsgrpg);
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $replaceproductid);
                    $replaceproductid2 = str_replace('[name]',"You", $replaceproductid1);
                    return $replaceproductid2;
                    break;                                    
                }                    
            case 'PPRPFNP':  
                if($masterlog == true){
                    $getmsgrpg = get_option('_rs_localize_log_for_nominated_user');                     
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $getmsgrpg);
                    $replaceproductid2 = str_replace('[points]',$nominatedpoints, $replaceproductid1);
                    $replaceproductid3 = str_replace('[name]',$usernickname, $replaceproductid2);
                    return $replaceproductid3;
                    break;                
                }else{
                    $getmsgrpg = get_option('_rs_localize_log_for_nominated_user');                     
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $getmsgrpg);
                    $replaceproductid2 = str_replace('[points]',$nominatedpoints, $replaceproductid1);
                    $replaceproductid3 = str_replace('[name]',"Your", $replaceproductid2);
                    return $replaceproductid3;
                    break;                
                } 
            case 'IMPADD':
                $getmsgrpg = get_option('_rs_localize_log_for_import_add');
                $replaceproductid2 = str_replace('[points]',$earnpoints, $getmsgrpg);
                return $replaceproductid2;
                break;
            case 'IMPOVR':
                if($masterlog == true){
                    $getmsgrpg = get_option('_rs_localize_log_for_import_override');
                    $replaceproductid2 = str_replace('[name]',$usernickname, $getmsgrpg);
                    return $replaceproductid2;
                    break;
                }else{
                    $getmsgrpg = get_option('_rs_localize_log_for_import_override');
                    $replaceproductid2 = str_replace('[name]',"Your", $getmsgrpg);
                    return $replaceproductid2;
                    break;
                }
            case 'RPFP':
                $getmsgrpg = get_option('_rs_localize_points_earned_for_post');
                $postname= get_the_title($productid) ; 
                $replacepostid = str_replace('{postid}',$postname, $getmsgrpg);
                return $replacepostid;
                break; 
            case 'SP': 
                if($masterlog == true){
                    $getmsgrpg = get_option('_rs_localize_log_for_reciver'); 
                    $replaceproductid = str_replace('[points]',$earnpoints, $getmsgrpg);
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $replaceproductid);
                    $replaceproductid2 = str_replace('[name]',$usernickname, $replaceproductid1);
                    return $replaceproductid2;
                    break;                                    
                }else{
                    $getmsgrpg = get_option('_rs_localize_log_for_reciver'); 
                    $replaceproductid = str_replace('[points]',$earnpoints, $getmsgrpg);
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $replaceproductid);
                    $replaceproductid2 = str_replace('[name]',"You", $replaceproductid1);
                    return $replaceproductid2;
                    break;                                    
                }  
                
            case 'SENPM':  
                if($masterlog == true){
                    $getmsgrpg = get_option('_rs_localize_log_for_sender');                    
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $getmsgrpg);
                    $replaceproductid2 = str_replace('[points]',$redeempoints, $replaceproductid1);
                    $replaceproductid3 = str_replace('[name]',$usernickname, $replaceproductid2);
                    return $replaceproductid3;
                    break;                
                }else{
                    $getmsgrpg = get_option('_rs_localize_log_for_sender');                 
                    $replaceproductid1 = str_replace('[user]',$nomineeid, $getmsgrpg);
                    $replaceproductid2 = str_replace('[points]',$redeempoints, $replaceproductid1);
                    $replaceproductid3 = str_replace('[name]',"Your", $replaceproductid2);
                    return $replaceproductid3;
                    break;                
                } 
            case 'SEP':
                $getmsgrpg=get_option('_rs_localize_points_to_send_log_revised');
                return $getmsgrpg;
		break;   
 
                
        }
    }
    
    public static function rewards_rs_order_status_control() {
        global $woocommerce;
        $orderslugs = array();

        if (function_exists('wc_get_order_statuses')) {
            $orderslugss = str_replace('wc-', '', array_keys(wc_get_order_statuses()));
            foreach ($orderslugss as $value) {
                if (is_array(get_option('rs_order_status_control'))) {
                    if (!in_array($value, get_option('rs_order_status_control'))) {
                        $orderslugs[] = $value;
                    }
                }
            }    
        } else {
            $taxonomy = 'shop_order_status';
            $orderstatus = '';
            $term_args = array(
                'hide_empty' => false,
                'orderby' => 'date',
            );
            $tax_terms = get_terms($taxonomy, $term_args);
            foreach ($tax_terms as $getterms) {
                if (is_array(get_option('rs_order_status_control'))) {
                    if (!in_array($getterms->slug, get_option('rs_order_status_control'))) {
                        $orderslugs[] = $getterms->slug;
                    }
                }
            }
    }
    update_option('rs_list_other_status', $orderslugs);
}


    public static function myrewardpoints_total_shortcode($content) {
        ob_start();
        $userid = get_current_user_id();
        $getusermeta = self::get_sum_of_total_earned_points($userid) + get_user_meta($userid, '_my_reward_points', true);
        if ($getusermeta != '') {
            $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
            echo get_option('rs_my_rewards_total') . " " . round(number_format((float) $getusermeta, 2, '.', ''), $roundofftype) . "</h4><br>";
        } else {
            echo get_option('rs_my_rewards_total') . " " . " 0</h4><br>";
        }
        $content = ob_get_clean();
        return $content;
    }
    
    public static function rs_fp_rewardsystem($atts) {
            ob_start();
            extract(shortcode_atts(array(
                'referralbutton' => 'show',
                'referraltable' => 'show',
                            ), $atts));
            if ($referralbutton == 'show') {
                RSFunctionForMyAccount::generate_referral_key();
            }
            if ($referraltable == 'show') {
                RSFunctionForMyAccount::list_table_array();
            }
            $maincontent = ob_get_clean();
            return $maincontent;
    }
    
    public static function shortcode_for_static_referral_link() {
            ob_start();                        
            $currentuserid = get_current_user_id();
            $objectcurrentuser = get_userdata($currentuserid);
            if (get_option('rs_generate_referral_link_based_on_user') == '1') {
                $referralperson = $objectcurrentuser->user_login;
            } else {
                $referralperson = $currentuserid;
            }
        
            $refurl = add_query_arg('ref', $referralperson, get_option('rs_static_generate_link'));
            ?><h3><?php _e('My Referral Link', 'rewardsystem'); ?></h3><?php
            echo $refurl;            
            $maincontent = ob_get_clean();
            return $maincontent;
    }

 public static function delete_cookie_after_some_purchase($cookievalue){  
       $countnoofpurchase =''; 
       $getnoofpurchase = get_user_meta(get_current_user_id(), 'rs_no_of_purchase_for_user',true);       
       if($getnoofpurchase != false){ 
        $countnoofpurchase = count($getnoofpurchase);          
       }               
       $checkenable = get_option('rs_enable_delete_referral_cookie_after_first_purchase'); 
       $noofpurchase = get_option('rs_no_of_purchase'); 
       if($checkenable == 'yes'){ 
           if(($noofpurchase != '') && ($noofpurchase != 0)){ 
               if($countnoofpurchase >= $noofpurchase){ 
                   setcookie('rsreferredusername', $cookievalue, time() - 3600, '/'); 
               } 
           } 
       } 
   }

}
new RSPointExpiry();