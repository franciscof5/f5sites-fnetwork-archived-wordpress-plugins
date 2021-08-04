<?php

function init_reward_gateway_class() {

    if (!class_exists('WC_Payment_Gateway'))
        return;

    class WC_Reward_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            global $woocommerce;
            $this->id = 'reward_gateway';
            $this->method_title = __('SUMO Reward Points Gateway', 'woocommerce');
            $this->has_fields = false; //Load Form Fields
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('gateway_titles');
            $this->description = $this->get_option('gateway_descriptions');
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Rewards Point Gateway', 'woowcommerce'),
                    'default' => 'no'
                ),
                'gateway_titles' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This Controls the Title which the user sees during checkout', 'woocommerce'),
                    'default' => __('SUMO Reward Points', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'gateway_descriptions' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Pay with your SUMO Reward Points', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'error_payment_gateway' => array(
                    'title' => 'Error Message',
                    'type' => 'textarea',
                    'description' => __('This Controls the errror message which is displayed during Checkout', 'woocommerce'),
                    'desc_tip' => true,
                    'default' => __('You need [needpoints] Points in your Account .But You have only [userpoints] Points.', 'rewardsystem'),
                ),
                'error_message_for_payment_gateway' => array(
                    'title' => 'Error Message for Payment Gateway',
                    'type' => 'textarea',
                    'description' => __('This Controls the error message which is displayed during Checkout', 'woocommerce'),
                    'desc_tip' => true,
                    'default' => __('Maximum Cart Total has been Limited to [maximum_cart_total]'),
                ),
            );
        }

function process_payment($order_id) {
    
            global $woocommerce;
                $total1=0;
                $totalpoints1=0;
                $totalpoints2=0;
                $totalvariable=0;
                $varpoints = array();
                $total11 = array();
                $points=array();
                 $total=array();
                 $order = new WC_Order($order_id);

                 
             if(get_option('rs_enable_disable_point_priceing')=='1'){
                 
                 update_post_meta($order_id,'pointsvalue','1');
                    foreach($order->get_items()as $item){                        
                        $product_id = $item['variation_id'] != 0 ? $item['variation_id'] :$item['product_id'];
                        
                        $points_array = RSFunctionForCart::calculate_point_price_for_products($product_id);
                        $points_each_product = (int)implode(",", $points_array);       
                        
               $enablevariable=RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
           if($enablevariable == '1'){
                $enablepoint=RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, 'pointsvalueenable');
          if($enablepoint=='1'){
               $quantity=$item['qty'];
          $getpoints = $points_each_product*$quantity;
          $varpoints[]=$getpoints;
          $total11[]= $item['line_total'];
          }
           }
            $enable=RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
     if($enable=='yes'){
          $enablepoint=RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, 'pointsvalueenable');
          if($enablepoint=='1'){
      $quantity=$item['qty'];
       
         $points[] = $points_each_product*$quantity;
          $total[]= $item['line_total'];
          }
       }
                    }
                    }
                    
                     $totalpoints2=array_sum($varpoints);
            
                    $totalpoints1=array_sum($points)+ $totalpoints2;
                    
                   $totalvariable=array_sum($total11);
                  
                     $total1= array_sum($total)+$totalvariable;
               
                      $total2=$order->get_total();
                    
                     $taxtotal = $order->get_total_tax();
                    
  if($totalpoints1>0){
       $total3=$total2-$total1;    
  }else{
       $total3=$total2;
  }
          
         
            $getuserid = $order->user_id;
            $couponcodeuserid = get_userdata($getuserid);
            $couponcodeuserlogin = $couponcodeuserid->user_login;
            $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");
            $current_conversion = get_option('rs_redeem_point');
            $point_amount = get_option('rs_redeem_point_value');
            $getmyrewardpoints = RSPointExpiry::get_sum_of_total_earned_points($getuserid);
            $getmaxoption = get_option('rs_max_redeem_discount_for_sumo_reward_points');

            if (isset($woocommerce->cart->coupon_discount_amounts["$usernickname"])) {
            $total4=$woocommerce->cart->coupon_discount_amounts[$usernickname];                                     
            $total5 = $total4 * $current_conversion;
            $total6 = $total5 / $point_amount;
            $userpoints=$getmyrewardpoints-$total6;            
            }else{
                $userpoints=$getmyrewardpoints != NULL?$getmyrewardpoints:'0';
            }

            $redeemedamount = $total3 * $current_conversion;
            $redeemedpoints1 = $redeemedamount / $point_amount;
            $redeemedpoints=$redeemedpoints1+$totalpoints1;
                        
            if ($redeemedpoints > $getmyrewardpoints) {
                $error_msg = $this->get_option('error_payment_gateway');
                $find = array('[userpoints]', '[needpoints]');
                $replace = array($userpoints, $redeemedpoints);
                $finalreplace = str_replace($find, $replace, $error_msg);
                wc_add_notice(__($finalreplace, 'woocommerce'), 'error');
                
                return;
            } else {
                
                
                if($getmaxoption != ''){                    
                    if ($getmaxoption > $ordertotal) {
                    $totalrewardpoints = $getmyrewardpoints - $redeemedpoints;
                        $user_id = $order->user_id;
                        $usedpoints = $redeemedpoints;
                        RSPointExpiry::perform_calculation_with_expiry($usedpoints, $user_id);
                        $equredeemamt = RSPointExpiry::redeeming_conversion_settings($usedpoints);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);


                        $noofdays = get_option('rs_point_to_be_expire');
                        if (($noofdays != '0') && ($noofdays != '')) {
                            $date = time() + ($noofdays * 24 * 60 * 60);
                        } else {
                            $date = '999999999999';
                        }
                        RSPointExpiry::record_the_points($user_id, '0', $usedpoints, $date, 'RPFGW', '0', $equredeemamt, $order_id, '0', '0', '0', '', $totalpoints, '', '0');
                        
                    } else {
                        $error_msg = $this->get_option('error_message_for_payment_gateway');
                        $find = array('[maximum_cart_total]');
                        $replace = $getmaxoption;
                        $finalreplace = str_replace($find, get_woocommerce_currency_symbol() . $replace, $error_msg);
                        wc_add_notice(__($finalreplace, 'woocommerce'), 'error');
                        return;
                    }
                }  else {
                    
                    
                        $user_id = $order->user_id;
                        $usedpoints = $redeemedpoints;
                        RSPointExpiry::perform_calculation_with_expiry($usedpoints, $user_id);
                        $equredeemamt = RSPointExpiry::redeeming_conversion_settings($usedpoints);
                        $totalpoints = RSPointExpiry::get_sum_of_total_earned_points($user_id);


                        $noofdays = get_option('rs_point_to_be_expire');
                        if (($noofdays != '0') && ($noofdays != '')) {
                            $date = time() + ($noofdays * 24 * 60 * 60);
                        } else {
                            $date = '999999999999';
                        }
                        RSPointExpiry::record_the_points($user_id, '0', $usedpoints, $date, 'RPFGW', '0', $equredeemamt, $order_id, '0', '0', '0', '', $totalpoints, '', '0');
                        
                       
                }
            }


            $order->payment_complete();
            $order_status = get_option('rs_order_status_after_gateway_purchase');
            
            $order->update_status($order_status);
            //Reduce Stock Levels
            $order->reduce_order_stock();

            //Remove Cart
            $woocommerce->cart->empty_cart();

            //Redirect the User
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
            wc_add_notice(__('Payment error:', 'woothemes') . $error_message, 'error');
            return;
        }

    }
    
    add_filter('woocommerce_available_payment_gateways', 'filter_gateway', 10, 1);

    function filter_gateway($gateways) {

        global $woocommerce;
        $enableproductpurchase = get_option('rs_enable_selected_product_for_purchase_using_points');
        if (($enableproductpurchase == 'yes')) {
            foreach ($woocommerce->cart->cart_contents as $key => $values) {
                $productid = $values['product_id'];

                if (!is_array(get_option('rs_select_product_for_purchase_using_points'))) {
                    if ((get_option('rs_select_product_for_purchase_using_points') != '' && (get_option('rs_select_product_for_purchase_using_points') != NULL))) {
                        $product_id = explode(',', get_option('rs_select_product_for_purchase_using_points'));
                    }
                } else {
                    $product_id = get_option('rs_select_product_for_purchase_using_points');
                }


                if (in_array($productid, (array) $product_id)) {
                    foreach (WC()->payment_gateways->payment_gateways() as $gateway) {
                        if ($gateway->id != 'reward_gateway') {
                            unset($gateways[$gateway->id]);
                        }
                    }
                }
            }
        }
        return $gateways != 'NULL' ? $gateways : array();
    }

    add_filter('woocommerce_add_to_cart_validation', 'sell_individually_for_point_pricing', 10, 6);

    function sell_individually_for_point_pricing($passed, $product_id, $product_quantity, $variation_id = '', $variatins = array(), $cart_item_data = array()) {
        global $woocommerce;
        $productnametodisplay = '';
        $sellindividuallyproducts = array();
        $excludedproductids = array();
        $msgtoreplace = array();
        $current_strtofind = "[productname]";
        $getstrtodisplay = get_option('rs_errmsg_when_other_products_added_to_cart_page');
        if (!is_array(get_option('rs_select_product_for_purchase_using_points'))) {
            $strtodisplay = explode(',', get_option('rs_select_product_for_purchase_using_points'));
        } else {
            $strtodisplay = get_option('rs_select_product_for_purchase_using_points');
        }

        if (is_array($strtodisplay)) {
            foreach ($strtodisplay as $values) {
                $productnametodisplay = get_the_title($values);
                $msgtoreplace[$values] = str_replace($current_strtofind, $productnametodisplay, $getstrtodisplay);
            }
        }

        $sellindividuallyproducts = $strtodisplay;
        $sellindividuallyproductsids = array();
        $productid = array();
        $getcartcount = $woocommerce->cart->cart_contents_count;
        foreach ($woocommerce->cart->cart_contents as $key => $values) {
            $productorvarid = $values['variation_id'] > '0' ? $values['variation_id'] : $values['product_id'];
            $productid[] = $values['product_id'];
            if (in_array($productorvarid, $sellindividuallyproducts)) {
                $sellindividuallyproductsids[] = $productorvarid;
            } else {
                $excludedproductids[] = $productorvarid;
            }
        }
        $enableproductpurchase = get_option('rs_enable_selected_product_for_purchase_using_points');
        if ($enableproductpurchase == 'yes') {
            $varorproid = $variation_id == '' ? $product_id : $variation_id;
            if (in_array($varorproid, $strtodisplay)) {
                if (empty($excludedproductids) && in_array($varorproid, $strtodisplay)) {
                    $passed = true;
                } else {
                    $woocommerce->cart->empty_cart();
                    $passed = true;
                }
            } else {
                if (is_array($sellindividuallyproductsids)) {
                    if (!empty($sellindividuallyproductsids)) {
                        $woocommerce->cart->empty_cart();
                        if (is_array($msgtoreplace)) {
                            foreach ($msgtoreplace as $eachmsg) {
                                wc_add_notice(__($eachmsg), 'error');
                            }
                        }
                    }
                }
                $passed = true;
            }
        }

        return $passed;
    }

    function add_your_gateway_class($methods) {
        if (is_user_logged_in()) {
            $userid = get_current_user_id();
            $banning_type = FPRewardSystem::check_banning_type($userid);
            if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                $methods[] = 'WC_Reward_Gateway';
            }
        }
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_your_gateway_class');
}
