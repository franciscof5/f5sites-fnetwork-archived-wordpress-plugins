<?php

class RSFunctionForCart {

    public function __construct() {

        add_action('admin_head',array($this,'rs_redeeming_selected_products_categories'));
        
        add_action('woocommerce_cart_totals_before_order_total', array($this, 'display_total_earned_points'));

        add_action('woocommerce_update_options_exclude_product_selection', array($this, 'save_product_to_exclude'));

        add_action('woocommerce_update_options_include_product_selection', array($this, 'save_product_to_include'));

        add_action('woocommerce_admin_field_exclude_product_selection', array($this, 'rs_select_product_to_exclude'));

        add_action('woocommerce_admin_field_include_product_selection', array($this, 'rs_select_product_to_include'));

        add_action('admin_head',array($this,'rs_validation_of_input_field_in_cart'));
        
        add_filter( 'wp_head', array($this, 'show_hide_coupon_code' ),1);

        if (get_option('rs_show_hide_redeem_field') == '1') {
            if (get_option('rs_reward_point_troubleshoot_after_cart') == '1') {
               add_action('woocommerce_after_cart_table', array($this, 'reward_system_add_message_after_cart_table'));
            } else {
                add_action('woocommerce_cart_coupon', array($this, 'reward_system_add_message_after_cart_table'));
            }
        }

        if (get_option('rs_reward_point_troubleshoot_before_cart') == '1') {
            add_action('woocommerce_before_cart', array($this, 'get_reward_points_to_display_msg_in_cart_and_checkout'));
        } else {
            add_action('woocommerce_before_cart_table', array($this, 'get_reward_points_to_display_msg_in_cart_and_checkout'));
        }
        add_action('woocommerce_before_checkout_form', array($this, 'get_reward_points_to_display_msg_in_cart_and_checkout'));

        if (get_option('rs_reward_point_troubleshoot_before_cart') == '1') {
            add_action('woocommerce_before_cart', array($this, 'display_msg_in_cart_page'));
        } else {
            add_action('woocommerce_before_cart_table', array($this, 'display_msg_in_cart_page'));
        }
        add_action('woocommerce_before_checkout_form', array($this, 'display_msg_in_checkout_page'));
        
        if (get_option('rs_reward_point_troubleshoot_before_cart') == '1') {
            add_action('woocommerce_before_cart', array($this, 'display_msg_in_cart_page_for_balance_reward_points'));
        } else {
            add_action('woocommerce_before_cart_table', array($this, 'display_msg_in_cart_page_for_balance_reward_points'));
        }
        add_action('woocommerce_before_checkout_form', array($this, 'display_msg_in_checkout_page_for_balance_reward_points'));
        
        add_shortcode('redeempoints', array($this, 'get_redeem_point_to_display_in_msg'));
        
        add_shortcode('rspoint', array($this, 'get_each_product_price_in_cart'));
        
        add_shortcode('titleofproduct', array($this, 'get_each_producttitle_in_cart'));
        
        add_shortcode('carteachvalue', array($this, 'get_each_product_points_value_in_cart'));
        
        add_shortcode('redeemeduserpoints', array($this, 'get_balance_redeem_points_to_display_in_msg'));
        
        add_action('wp_head',array($this,'validation_in_my_cart'));
        
        
        add_action('woocommerce_before_cart', array($this, 'display_redeem_points_buttons_on_cart_page'));
        
        add_shortcode('rsminimumpoints', array($this, 'get_minimum_redeeming_points_value'));
        
        add_shortcode('rsmaximumpoints', array($this, 'get_maximum_redeeming_points_value'));
         
        add_shortcode('rsequalpoints', array($this, 'get_minimum_and_maximum_redeeming_points_value'));
        
        add_filter('woocommerce_cart_totals_coupon_label', array($this, 'change_coupon_label'), 1, 2);
        
        add_filter('woocommerce_add_to_cart_validation', array($this, 'sell_individually_functionality'), 10, 5);

        add_filter('woocommerce_cart_item_price', array($this, 'display_points_price'), 10, 3);

        add_filter('woocommerce_cart_item_subtotal', array($this, 'display_points_total'), 10, 3);

        add_action('woocommerce_cart_total', array($this, 'total_points_display_in_cart'));

       // add_filter('woocommerce_get_cart_item_from_session', array($this, 'cart_item_from_session'), 99, 3);

        //add_action('init', array($this, 'update_cart_action'), 9);
        // add_action('woocommerce_add_order_item_meta', array($this, 'add_item_meta'), 10, 2);
       
        add_action('woocommerce_add_to_cart', array($this, 'set_point_price_for_products_in_session'), 1, 5);
        
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_point_price_info_in_order'));
        
       
    }
    
    
     function add_item_meta($item_id, $values) {
           $enablepoint=RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($values['product_id'], 'pointsvalueenable');
           $vaiationenable= RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($values['variation_id'], 'pointsvalueenable');
          if($enablepoint=='1'||$vaiationenable=='1'){
              $points=isset($values['points'])?$values['points']:'';
        woocommerce_add_order_item_meta($item_id, '_points', $points);
          }else{
               woocommerce_add_order_item_meta($item_id, '_points', '');
          }
    }

   

    function update_cart_action() {
        global $woocommerce;
        if ((!empty($_POST['update_cart']) || !empty($_POST['proceed']) ) && $woocommerce->verify_nonce('cart')) {
            $cart_totals = isset($_POST['cart']) ? $_POST['cart'] : '';
            if (sizeof($woocommerce->cart->get_cart()) > 0) {
                foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {
                    if (isset($cart_totals[$cart_item_key]['points'])) {
                        $woocommerce->cart->cart_contents[$cart_item_key]['points'] = $cart_totals[$cart_item_key]['points'];
                    }
                }
            }
        }
    }
    
    public static function set_point_price_for_products_in_session($cart_item_key, $product_id = null, $quantity = null, $variation_id = null, $variation = null){        
        $product_id = $variation_id != null ? $variation_id :$product_id;
        $point_price_for_product = self::calculate_point_price_for_products($product_id);
        WC()->session->set($cart_item_key . 'point_price_for_product', $point_price_for_product);
    }
    
    public static function save_point_price_info_in_order($orderid){
        global $woocommerce;
        $current_cart_contents = $woocommerce->cart->cart_contents;
        foreach ($current_cart_contents as $key => $value) {
            if (WC()->session->get($key . 'point_price_for_product')) {
                 $point_price_info[] = WC()->session->get($key . 'point_price_for_product');
                update_post_meta($orderid,'point_price_for_product_in_order',$point_price_info);
            }
        }        
    }
    
    
    
        public static function calculate_point_price_for_products($product_id) {            
        if (get_option('rs_enable_disable_point_priceing') == '1') {
            $global_enable = get_option('rs_local_enable_disable_point_price_for_product');
            $points = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem__points');            
            $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
            $checkenablevariation = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
            $variablerewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, 'price_points');
            $variable_product1 = new WC_Product_Variation($product_id);
            $checkproduct = get_product($product_id);
            $checkanotherproduct = get_product($product_id);
            $data = array();
            if ($enable == 'yes') {
 
                if ($points == '') {
                    $term = get_the_terms($product_id, 'product_cat');

                    if (is_array($term)) {
                        $rewardpoints = array('0');
                        foreach ($term as $term) {

                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_point_price_category', true);

                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);

                            if (($enablevalue == 'yes') && ($enablevalue != '')) {

                                $checktermpoints = get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true);
                                if ($checktermpoints == '') {

                                    if ($global_enable == '1') {
                                        if (get_option('rs_local_price_points_for_product') != '') {

                                            $data[$product_id] = get_option('rs_local_price_points_for_product');
                                             RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                        } else {
                                            $data[$product_id] = '';
                                        }
                                    }
                                } else {

                                    $data[$product_id] = get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true);
                                     RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                }
                            } else {
                                if ($global_enable == '1') {

                                    $data[$product_id] = get_option('rs_local_price_points_for_product');
                                     RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                }
                            }
                        }
                    } else {

                        if (get_option('rs_local_price_points_for_product') != '') {

                            $data[$product_id] = get_option('rs_local_price_points_for_product');
                             RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                        } else {
                            $data[$product_id] = '';
                        }
                    }
                } else {
                    $data[$product_id] = $points;
                     RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                }     
            }
                
            if ($checkenablevariation == '1') {
                $parentvariationid = new WC_Product_Variation($product_id);                
                $newparentid = $parentvariationid->parent->id;

                if ($variablerewardpoints == '') {
                    $term = get_the_terms($newparentid, 'product_cat');

                    if (is_array($term)) {

                        foreach ($term as $term) {

                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_point_price_category', true);

                            if (($enablevalue == 'yes') && ($enablevalue != '')) {

                                if (get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true) == '') {
                                    $global_enable = get_option('rs_local_enable_disable_point_price_for_product');

                                    if (get_option('rs_local_price_points_for_product') != '') {

                                        $data[$product_id] = get_option('rs_local_price_points_for_product');
                                         RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                    } else {
                                        $data[$product_id] = '';
                                    }
                                } else {
                                    $data[$product_id] = get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true);
                                      RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                }
                            } else {
                                if ($global_enable == '1') {

                                    $data[$product_id] = get_option('rs_local_price_points_for_product');
                                      RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                                }
                            }
                        }
                    } else {

                        if (get_option('rs_local_price_points_for_product') != '') {

                            $data[$product_id] = get_option('rs_local_price_points_for_product');
                              RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                        } else {
                            $data[$product_id] = '';
                        }
                    }
                } else {
                    $data[$product_id] = $variablerewardpoints;
                      RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($product_id, 'pointsvalueenable','1');
                }
            }

            return $data;
        }
    }
    
    
  
    public static function total_points_display_in_cart($price) {
       
        global $woocommerce;
        $total1 = 0;
        $totalpoints1 = 0;
        $totalpoints2 = 0;
        $totalvariable = 0;
        $varpoints = array();
        $total11 = array();
        $points = array();
        $total = array();
        $labelpoint = get_option('rs_label_for_point_value');

        if (get_option('rs_enable_disable_point_priceing') == '1') {

            $shippingcost = $woocommerce->shipping->shipping_total;

            $shipping_tax = $woocommerce->shipping->shipping_taxes;

            $shipping_tax_total = array_sum($shipping_tax);


            $taxtotal = $woocommerce->cart->get_taxes();

            $taxtotal1 = array_sum($taxtotal);

            $shippingcost_total = $taxtotal1 + $shippingcost;

            foreach ($woocommerce->cart->cart_contents as $key) {

                $total22[] = $key['line_subtotal'];
                $product_id = $key['variation_id'] != 0 ? $key['variation_id'] :$key['product_id'];                
                $enablevariable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
                $points_array = self::calculate_point_price_for_products($product_id);
                if($points_array != NULL){
                    $points = (int)implode(",", $points_array);                        

                    $opto1[] = $enablevariable;
                    if ($enablevariable == '1') {
                        $quantity = $key['quantity'];
                        $getpoints = $points * $quantity;

                        $varpoints[] = $getpoints;
                        $total11[] = $key['line_subtotal'];
                    }

                    $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');                
                    $opto[] = $enable;
                    if ($enable == 'yes') {

                        $quantity = $key['quantity'];                                       

                        $get_points[] = $points * $quantity;


                        $total[] = $key['line_subtotal'];                    
                    }
                }
            }
            
            
            $current_conversion1 = get_option('rs_redeem_point');
            $point_amount1 = get_option('rs_redeem_point_value');
            $redeemedamount1 = $shippingcost_total * $current_conversion1;
            $redeemedpoints2 = $redeemedamount1 / $point_amount1;
            
            if(!empty($varpoints) && !empty($get_points)){
                $totalpoints2 = array_sum($varpoints);            
                $totalpoints1 = array_sum($get_points) + $totalpoints2;            
                $totalvariable = array_sum($total11);
                $total1 = array_sum($total) + $totalvariable;

                $total2 = array_sum($total22);

                if ($totalpoints1 > 0) {
                    $total3 = $total2 - $total1;
                } else {
                    $total3 = $total2;
                }
                $current_conversion = get_option('rs_redeem_point');
                $point_amount = get_option('rs_redeem_point_value');
                $redeemedamount = $total3 * $current_conversion;
                $redeemedpoints1 = $redeemedamount / $point_amount;

                $redeemedpoints = $redeemedpoints1 + $totalpoints1 + $redeemedpoints2;
                $update = $labelpoint . $redeemedpoints;

                if (in_array("yes", $opto) || in_array("1", $opto1)) {
                    if ($totalpoints1 > 0) {
                        return $price . $update;
                    } else {
                        return $price;
                    }
                } else {
                    return $price;
                }
            }else{
                return $price;
            }
            
        } else {
            return $price;
        }
    }

    public static function display_points_price($product_price, $item, $item_key) {
        $product_id = $item['variation_id'] != 0 ? $item['variation_id'] :$item['product_id'];  
        if (get_option('rs_enable_disable_point_priceing') == '1') {
            $enablevariable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
            $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
            if ($enablevariable == '1' || $enable == 'yes') {
                $quantity = $item['quantity'];
                $labelpoint = get_option('rs_label_for_point_value');                
                $points_array = self::calculate_point_price_for_products($product_id);
                $points = implode(",", $points_array);                
               if($points!=''){
                $product_price = wc_price($item['data']->price) . $labelpoint . $points;
                return $product_price;
               }else{
                   return $product_price;
               }
            } else {
                return $product_price;
            }
        } else {
            return $product_price;
        }
    }

    public static function display_points_total($product_price, $item, $item_key) {
         $product_id = $item['variation_id'] != 0 ? $item['variation_id'] : $item['product_id'];
        if (get_option('rs_enable_disable_point_priceing') == '1') {
            $enablevariable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
            $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
            if ($enablevariable == '1' || $enable == 'yes') {
                $quantity = $item['quantity'];
                $labelpoint = get_option('rs_label_for_point_value');
                $id = $item['product_id'];

                $points_array = self::calculate_point_price_for_products($product_id);
                $points = implode(",", $points_array);
                if ($points != '') {
                    $product_price = wc_price($item['line_subtotal']) . $labelpoint . $points * $quantity;
                    return $product_price;
                } else {
                    return $product_price;
                }
            } else {
                return $product_price;
            }
        } else {
            return $product_price;
        }
    }

    public static function sell_individually_functionality($valid, $product_id, $quantity, $variation_id = NULL, $variations = NULL) {
        if (get_option('rs_enable_disable_point_priceing') == '1') {
            if (function_exists('WC')) {

                $cart_content = WC()->cart->get_cart();

                if (!empty($cart_content)) {
                    $cart_contents_count = WC()->cart->cart_contents_count;
                    foreach ($cart_content as $key => $content) {
                        if ($cart_contents_count > 0 && 1 <= $cart_contents_count) {


                            if (isset($content['data']->variation_id)) {
                                $productid = $content['data']->variation_id;
                            } else {
                                $productid = $content['data']->id;
                            }


                            if (self::check_is_point_pricing_enable($productid)) {

                                if (isset($variation_id)) {
                                    $get_product_productid = $variation_id;
                                } else {
                                    $get_product_productid = $product_id;
                                }

                                if (self::check_is_point_pricing_enable($get_product_productid)) {
                                    if (isset($variation_id)) {

                                        if ($variation_id == $content['data']->variation_id) {

                                            $valid = false;
                                            wc_add_notice(get_option('rs_errmsg_for_point_price_product_with_same'), 'error');
                                            return $valid;
                                        }
                                    } else {
                                        if ($product_id == $content['data']->id) {

                                            $valid = false;
                                            wc_add_notice(get_option('rs_errmsg_for_point_price_product_with_same'), 'error');
                                            return $valid;
                                        }
                                    }
                                } else {


                                    $valid = false;
                                    wc_add_notice(get_option('rs_errmsg_for_normal_product_with_point_price'), 'error');
                                    return $valid;
                                }
                            } else {
                                if (isset($variation_id)) {
                                    $get_product_productid = $variation_id;
                                } else {
                                    $get_product_productid = $product_id;
                                }
                                if (self::check_is_point_pricing_enable($get_product_productid)) {

                                    $valid = false;
                                    wc_add_notice(get_option('rs_errmsg_for_point_price_product_with_normal'), 'error');
                                    return $valid;
                                } else {

                                    $valid = true;
                                    return $valid;
                                }
                            }
                        } else {
                            if (isset($variation_id)) {
                                $get_product_productid = $variation_id;
                            } else {
                                $get_product_productid = $product_id;
                            }




                            if (self::check_is_point_pricing_enable($get_product_productid)) {


                                if (self::check_cart_contain_subscription()) {
                                    $valid = false;
                                    wc_add_notice("you cannot add more than one product", 'error');
                                    return $valid;
                                } else {
                                    WC()->cart->empty_cart();
                                    $valid = true;
                                    wc_add_notice("cannot add normal product with point pricing product", 'error');
                                    return $valid;
                                }
                            } else {

                                $valid = true;
                                return $valid;
                            }
                        }
                    }
                }
            }
        }
        return $valid;
    }

    public static function check_is_point_pricing_enable($productid) {

        if (get_option('rs_enable_disable_point_priceing') == '1') {
            $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_enable_reward_points_price');
           $checkenablevariation = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_enable_reward_points_price');
            $variablerewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, 'price_points');
              if ($checkenablevariation == '1') {

                $parentvariationid = new WC_Product_Variation($productid);
                $newparentid = $parentvariationid->parent->id;

                if ($variablerewardpoints == '') {
                    $term = get_the_terms($newparentid, 'product_cat');

                    if (is_array($term)) {

                        foreach ($term as $term) {

                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_point_price_category', true);

                            if (($enablevalue == 'yes') && ($enablevalue != '')) {

                                if (get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true) == '') {
                                    $global_enable = get_option('rs_local_enable_disable_point_price_for_product');

                                    if (get_option('rs_local_price_points_for_product') != '') {

                                       return true;
                                    } else {
                                       return false;
                                    }
                                } else {
                                    return true;
                                }
                            } else {
                                if ($global_enable == '1') {

                                    return true;
                                }
                            }
                        }
                    } else {

                        if (get_option('rs_local_price_points_for_product') != '') {

                           return true;
                        } else {
                          return false;
                        }
                    }
                } else {
                    return true;
                }
            }

            
            

            $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem_enable_point_price');
 $points = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($productid, '_rewardsystem__points');
             if ($enable == 'yes') {

                if ($points == '') {
                    $term = get_the_terms($productid, 'product_cat');

                    if (is_array($term)) {
                        $rewardpoints = array('0');
                        foreach ($term as $term) {

                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_point_price_category', true);

                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);

                            if (($enablevalue == 'yes') && ($enablevalue != '')) {

                                $checktermpoints = get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true);
                                if ($checktermpoints == '') {

                                    if ($global_enable == '1') {
                                        if (get_option('rs_local_price_points_for_product') != '') {

                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                } else {

                                    return true;
                                }
                            } else {
                                if ($global_enable == '1') {

                                    return true;
                                }
                            }
                        }
                    } else {

                        if (get_option('rs_local_price_points_for_product') != '') {

                            return true;
                        } else {
                            return false;
                        }
                    }
                } else {
                    return true;
                }
            }

            
            
            
          
        }
    }


    /* Function for hiding the couon field */
    public static function show_hide_coupon_code() {		
            
        if (is_cart()) {
            if (get_option('rs_show_hide_coupon_field') == 2) {
                ?>
                <style type="text/css">
                    .coupon{
                        display: none;
                    }
                </style>
                <?php
            }
        }
        
    }

    /*
     * Function to show when enable and hide when disable
     */
    public static function rs_redeeming_selected_products_categories() {
        global $woocommerce;
        if (isset($_GET['tab'])) {
            if ($_GET['tab'] == 'rewardsystem_cart') {
                ?>
                <?php
                echo RSJQueryFunction::rs_common_ajax_function_to_select_products('rs_ajax_chosen_select_products_redeem');
                if ((float) $woocommerce->version <= (float) ('2.2.0')) {
                    echo RSJQueryFunction::rs_common_chosen_function('#rs_select_category_to_enable_redeeming');
                    echo RSJQueryFunction::rs_common_chosen_function('#rs_exclude_category_to_enable_redeeming');
                }else{
                    echo RSJQueryFunction::rs_common_select_function('#rs_select_category_to_enable_redeeming');
                    echo RSJQueryFunction::rs_common_select_function('#rs_exclude_category_to_enable_redeeming');
                }

                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                            var currentvalue=jQuery('#rs_show_hide_redeem_field').val();
                            if(currentvalue==='1'){
                                jQuery('#rs_enable_redeem_for_selected_products').parent().parent().parent().parent().show();
                                jQuery('#rs_exclude_products_for_redeeming').parent().parent().parent().parent().show();
                                jQuery('#rs_enable_redeem_for_selected_category').parent().parent().parent().parent().show();
                                jQuery('#rs_exclude_category_for_redeeming').parent().parent().parent().parent().show();
                                var enable_selected_product_checkbox =jQuery('#rs_enable_redeem_for_selected_products').is(':checked')?'yes':'no';
                                var enable_exclude_product_checkbox = jQuery('#rs_exclude_products_for_redeeming').is(':checked') ? 'yes' : 'no';
                                var enable_selected_category_checkbox = jQuery('#rs_enable_redeem_for_selected_category').is(':checked') ? 'yes' : 'no';
                                var enable_exclude_category_checkbox = jQuery('#rs_exclude_category_for_redeeming').is(':checked') ? 'yes' : 'no';
                                if(enable_selected_product_checkbox==='yes'){
                                    jQuery('#rs_select_products_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_exclude_product_checkbox==='yes'){
                                    jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_selected_category_checkbox==='yes'){
                                    jQuery('#rs_select_category_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_exclude_category_checkbox==='yes'){
                                    jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                                }


                                //When enabling the product and category
                                jQuery('#rs_enable_redeem_for_selected_products').click(function(){
                                    var enable_redeem_for_selected_product=jQuery('#rs_enable_redeem_for_selected_products').is(':checked')?'yes':'no';
                                    if(enable_redeem_for_selected_product=='yes'){
                                        jQuery('#rs_select_products_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_exclude_products_for_redeeming').click(function(){
                                    var enable_exclude_product_checkbox = jQuery('#rs_exclude_products_for_redeeming').is(':checked') ? 'yes' : 'no';
                                    if(enable_exclude_product_checkbox=='yes'){
                                        jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_enable_redeem_for_selected_category').click(function(){
                                    var enable_selected_category_checkbox = jQuery('#rs_enable_redeem_for_selected_category').is(':checked') ? 'yes' : 'no';
                                    if(enable_selected_category_checkbox=='yes'){
                                        jQuery('#rs_select_category_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_exclude_category_for_redeeming').click(function(){
                                    var enable_exclude_category_checkbox = jQuery('#rs_exclude_category_for_redeeming').is(':checked') ? 'yes' : 'no';
                                    if(enable_exclude_category_checkbox=='yes'){
                                        jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                            }else{
                                jQuery('#rs_enable_redeem_for_selected_products').parent().parent().parent().parent().hide();
                                jQuery('#rs_exclude_products_for_redeeming').parent().parent().parent().parent().hide();
                                jQuery('#rs_enable_redeem_for_selected_category').parent().parent().parent().parent().hide();
                                jQuery('#rs_exclude_category_for_redeeming').parent().parent().parent().parent().hide();
                                jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                            }

                        jQuery('#rs_show_hide_redeem_field').change(function(){
                            var currentvalue=jQuery(this).val();
                            if(currentvalue==='1'){
                                jQuery('#rs_enable_redeem_for_selected_products').parent().parent().parent().parent().show();
                                jQuery('#rs_exclude_products_for_redeeming').parent().parent().parent().parent().show();
                                jQuery('#rs_enable_redeem_for_selected_category').parent().parent().parent().parent().show();
                                jQuery('#rs_exclude_category_for_redeeming').parent().parent().parent().parent().show();
                                var enable_selected_product_checkbox =jQuery('#rs_enable_redeem_for_selected_products').is(':checked')?'yes':'no';
                                var enable_exclude_product_checkbox = jQuery('#rs_exclude_products_for_redeeming').is(':checked') ? 'yes' : 'no';
                                var enable_selected_category_checkbox = jQuery('#rs_enable_redeem_for_selected_category').is(':checked') ? 'yes' : 'no';
                                var enable_exclude_category_checkbox = jQuery('#rs_exclude_category_for_redeeming').is(':checked') ? 'yes' : 'no';
                                if(enable_selected_product_checkbox==='yes'){
                                    jQuery('#rs_select_products_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_exclude_product_checkbox==='yes'){
                                    jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_selected_category_checkbox==='yes'){
                                    jQuery('#rs_select_category_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                }
                                if(enable_exclude_category_checkbox==='yes'){
                                    jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().show();
                                }else{
                                    jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                                }


                                //When enabling the product and category
                                jQuery('#rs_enable_redeem_for_selected_products').click(function(){
                                    var enable_redeem_for_selected_product=jQuery('#rs_enable_redeem_for_selected_products').is(':checked')?'yes':'no';
                                    if(enable_redeem_for_selected_product=='yes'){
                                        jQuery('#rs_select_products_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_exclude_products_for_redeeming').click(function(){
                                    var enable_exclude_product_checkbox = jQuery('#rs_exclude_products_for_redeeming').is(':checked') ? 'yes' : 'no';
                                    if(enable_exclude_product_checkbox=='yes'){
                                        jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_enable_redeem_for_selected_category').click(function(){
                                    var enable_selected_category_checkbox = jQuery('#rs_enable_redeem_for_selected_category').is(':checked') ? 'yes' : 'no';
                                    if(enable_selected_category_checkbox=='yes'){
                                        jQuery('#rs_select_category_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                                jQuery('#rs_exclude_category_for_redeeming').click(function(){
                                    var enable_exclude_category_checkbox = jQuery('#rs_exclude_category_for_redeeming').is(':checked') ? 'yes' : 'no';
                                    if(enable_exclude_category_checkbox=='yes'){
                                        jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().show();
                                    }else{
                                        jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                                    }
                                });
                            }else{
                                jQuery('#rs_enable_redeem_for_selected_products').parent().parent().parent().parent().hide();
                                jQuery('#rs_exclude_products_for_redeeming').parent().parent().parent().parent().hide();
                                jQuery('#rs_enable_redeem_for_selected_category').parent().parent().parent().parent().hide();
                                jQuery('#rs_exclude_category_for_redeeming').parent().parent().parent().parent().hide();
                                jQuery('#rs_select_products_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_exclude_products_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_select_category_to_enable_redeeming').parent().parent().hide();
                                jQuery('#rs_exclude_category_to_enable_redeeming').parent().parent().hide();
                            }
                        });


                        //Show or Hide Redeeming Field Caption
                        if(jQuery('#rs_show_hide_redeem_caption').val() == '1'){
                            jQuery('#rs_redeem_field_caption').parent().parent().show();
                        }else{
                            jQuery('#rs_redeem_field_caption').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_redeem_caption').change(function(){
                            if(jQuery('#rs_show_hide_redeem_caption').val() == '1'){
                                jQuery('#rs_redeem_field_caption').parent().parent().show();
                            }else{
                                jQuery('#rs_redeem_field_caption').parent().parent().hide();
                            }
                        });

                        //Show or Hide Redeeming Field Placeholder
                        if(jQuery('#rs_show_hide_redeem_placeholder').val() == '1'){
                            jQuery('#rs_redeem_field_placeholder').parent().parent().show();
                        }else{
                            jQuery('#rs_redeem_field_placeholder').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_redeem_placeholder').change(function(){
                            if(jQuery('#rs_show_hide_redeem_placeholder').val() == '1'){
                                jQuery('#rs_redeem_field_placeholder').parent().parent().show();
                            }else{
                                jQuery('#rs_redeem_field_placeholder').parent().parent().hide();
                            }
                        });

                        //Show or Hide Current User Points is Empty Error Message
                        if(jQuery('#rs_show_hide_points_empty_error_message').val() == '1'){
                            jQuery('#rs_current_points_empty_error_message').parent().parent().show();
                        }else{
                            jQuery('#rs_current_points_empty_error_message').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_points_empty_error_message').change(function(){
                            if(jQuery('#rs_show_hide_points_empty_error_message').val() == '1'){
                                jQuery('#rs_current_points_empty_error_message').parent().parent().show();
                            }else{
                                jQuery('#rs_current_points_empty_error_message').parent().parent().hide();
                            }
                        });

                        //Show or Hide Minimum Points for first time Redeeming Error Message
                        if(jQuery('#rs_show_hide_first_redeem_error_message').val() == '1'){
                            jQuery('#rs_min_points_first_redeem_error_message').parent().parent().show();
                        }else{
                            jQuery('#rs_min_points_first_redeem_error_message').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_first_redeem_error_message').change(function(){
                            if(jQuery('#rs_show_hide_first_redeem_error_message').val() == '1'){
                                jQuery('#rs_min_points_first_redeem_error_message').parent().parent().show();
                            }else{
                                jQuery('#rs_min_points_first_redeem_error_message').parent().parent().hide();
                            }
                        });

                        //Show or Hide Minimum Points After first time Redeeming Error Message
                        if(jQuery('#rs_show_hide_after_first_redeem_error_message').val() == '1'){
                            jQuery('#rs_min_points_after_first_error').parent().parent().show();
                        }else{
                            jQuery('#rs_min_points_after_first_error').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_after_first_redeem_error_message').change(function(){
                            if(jQuery('#rs_show_hide_after_first_redeem_error_message').val() == '1'){
                                jQuery('#rs_min_points_after_first_error').parent().parent().show();
                            }else{
                                jQuery('#rs_min_points_after_first_error').parent().parent().hide();
                            }
                        });

                        //Show or Hide Minimum Cart Total for Redeeming Error Message
                        if(jQuery('#rs_show_hide_minimum_cart_total_error_message').val() == '1'){
                            jQuery('#rs_min_cart_total_redeem_error').parent().parent().show();
                        }else{
                            jQuery('#rs_min_cart_total_redeem_error').parent().parent().hide();
                        }

                        jQuery('#rs_show_hide_minimum_cart_total_error_message').change(function(){
                            if(jQuery('#rs_show_hide_minimum_cart_total_error_message').val() == '1'){
                                jQuery('#rs_min_cart_total_redeem_error').parent().parent().show();
                            }else{
                                jQuery('#rs_min_cart_total_redeem_error').parent().parent().hide();
                            }
                        });
                        
                        
                        //Show or Hide For Redeem Button Type 
                        if(jQuery('#rs_redeem_field_type_option').val() == '1'){
                            jQuery('#rs_percentage_cart_total_redeem').parent().parent().hide();
                        }else{
                            jQuery('#rs_percentage_cart_total_redeem').parent().parent().show();
                        }

                        jQuery('#rs_redeem_field_type_option').change(function(){
                            if(jQuery('#rs_redeem_field_type_option').val() == '1'){
                                jQuery('#rs_percentage_cart_total_redeem').parent().parent().hide();
                            }else{
                                jQuery('#rs_percentage_cart_total_redeem').parent().parent().show();
                            }
                        });

                    });
                </script>
                <?php
            }
        }
    }
public static function display_total_earned_points($param) {
    if(get_option('rs_show_hide_total_points_cart_field') == '1'){
     $totalrewardpoints = do_shortcode('[totalrewards]');
     if($totalrewardpoints != 0){   
        ?>
       <div class="cart_totals" >


       <tr class="cart-totalvalue">
              <th><?php echo get_option('rs_total_earned_point_caption'); ?></th>
              <td><?php echo $totalrewardpoints; ?></td>
       </tr>
       </div>
      <?php
    }
   }
   $totalrewardpoints = do_shortcode('[totalrewards]');
   WC()->session->set('rewardpoints',$totalrewardpoints);
}
    /*
     * Function to select products to exclude
     */
    public static function rs_select_product_to_exclude() {

        global $woocommerce;
        if ((float) $woocommerce->version > (float) ('2.2.0')) {
            ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_exclude_products_to_enable_redeeming"><?php _e('Select Products', 'rewardsystem'); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <input type="hidden" class="wc-product-search" style="width: 350%;" id="rs_exclude_products_to_enable_redeeming"  name="rs_exclude_products_to_enable_redeeming" data-placeholder="<?php _e('Search for a product&hellip;', 'rewardsystem'); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" data-selected="<?php
                    $json_ids = array();
                    if (get_option('rs_exclude_products_to_enable_redeeming') != "") {
                        $list_of_produts = get_option('rs_exclude_products_to_enable_redeeming');
                        $product_ids = array_filter(array_map('absint', (array) explode(',', get_option('rs_exclude_products_to_enable_redeeming'))));

                        foreach ($product_ids as $product_id) {
                            $product = wc_get_product($product_id);
                            $json_ids[$product_id] = wp_kses_post($product->get_formatted_name());
                        } echo esc_attr(json_encode($json_ids));
                    }
                    ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" />
                </td>
            </tr>
        <?php } else { ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_exclude_products_to_enable_redeeming"><?php _e('Select Products', 'rewardsystem'); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <select multiple name="rs_exclude_products_to_enable_redeeming[]" style='width:350px;' id='rs_exclude_products_to_enable_redeeming' class="rs_exclude_products_to_enable_redeeming rs_ajax_chosen_select_products_redeem">
                        <?php
                        $selected_products_exclude = array_filter((array) get_option('rs_exclude_products_to_enable_redeeming'));
                        if ($selected_products_exclude != "") {
                            if(!empty($selected_products_exclude)){
                            $list_of_produts = (array) get_option('rs_exclude_products_to_enable_redeeming');
                            foreach ($list_of_produts as $rs_free_id) {
                                echo '<option value="' . $rs_free_id . '" ';
                                selected(1, 1);
                                echo '>' . ' #' . $rs_free_id . ' &ndash; ' . get_the_title($rs_free_id);
                            }
                            }
                        } else {
                            ?>
                            <option value=""></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php
        }
    }

    /*
     * Function to select products to include
     */
    public static function rs_select_product_to_include() {

        global $woocommerce;
        if ((float) $woocommerce->version > (float) ('2.2.0')) {
            ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_select_products_to_enable_redeeming"><?php _e('Select Products', 'rewardsystem'); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <input type="hidden" class="wc-product-search" style="width: 350%;" id="rs_select_products_to_enable_redeeming"  name="rs_select_products_to_enable_redeeming" data-placeholder="<?php _e('Search for a product&hellip;', 'rewardsystem'); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" data-selected="<?php
                    $json_ids = array();
                    if (get_option('rs_select_products_to_enable_redeeming') != "") {
                        $list_of_produts = get_option('rs_select_products_to_enable_redeeming');
                        $product_ids = array_filter(array_map('absint', (array) explode(',', get_option('rs_select_products_to_enable_redeeming'))));

                        foreach ($product_ids as $product_id) {
                            $product = wc_get_product($product_id);
                            $json_ids[$product_id] = wp_kses_post($product->get_formatted_name());
                        } echo esc_attr(json_encode($json_ids));
                    }
                    ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" />
                </td>
            </tr>
        <?php } else { ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_select_products_to_enable_redeeming"><?php _e('Select Products', 'rewardsystem'); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <select multiple name="rs_select_products_to_enable_redeeming[]" style='width:350px;' id='rs_select_products_to_enable_redeeming' class="rs_select_products_to_enable_redeeming rs_ajax_chosen_select_products_redeem">
                        <?php
                        $selected_products_include =  array_filter((array) get_option('rs_select_products_to_enable_redeeming'));
                        if ($selected_products_include != "") {
                            if(!empty($selected_products_include)){
                            $list_of_produts = (array) get_option('rs_select_products_to_enable_redeeming');
                            foreach ($list_of_produts as $rs_free_id) {
                                echo '<option value="' . $rs_free_id . '" ';
                                selected(1, 1);
                                echo '>' . ' #' . $rs_free_id . ' &ndash; ' . get_the_title($rs_free_id);
                            }
                            }
                        } else {
                            ?>
                            <option value=""></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php
        }
    }

    /*
     * Function to save the selected products to exclude
     */
    public static function save_product_to_exclude() {
        update_option('rs_exclude_products_to_enable_redeeming', $_POST['rs_exclude_products_to_enable_redeeming']);
    }

    /*
     * Function to save select products to include
     */
    public static function save_product_to_include() {
        update_option('rs_select_products_to_enable_redeeming', $_POST['rs_select_products_to_enable_redeeming']);
    }

    public static function rs_validation_of_input_field_in_cart(){
        ?>

        <script type="text/javascript">
            jQuery(function () {
                jQuery('body').on('blur', '#rs_percentage_cart_total_redeem[type=text],\n\
                                           #rs_first_time_minimum_user_points[type=text],\n\
                                           #rs_minimum_user_points_to_redeem[type=text],\n\
                                           #rs_minimum_redeeming_points[type=text],\n\\n\
                                           #rs_maximum_redeeming_points[type=text],\n\
                                           #rs_minimum_cart_total_points[type=text]', function () {
                    jQuery('.wc_error_tip').fadeOut('100', function () {
                        jQuery(this).remove();
                    });

                    return this;
                });

                jQuery('body').on('keyup change', '#rs_percentage_cart_total_redeem[type=text],\n\
                                           #rs_first_time_minimum_user_points[type=text],\n\
                                           #rs_minimum_user_points_to_redeem[type=text],\n\
                                           #rs_minimum_redeeming_points[type=text],\n\\n\
                                           #rs_maximum_redeeming_points[type=text],\n\
                                           #rs_minimum_cart_total_points[type=text]', function () {
                    var value = jQuery(this).val();
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

    public static function reward_system_add_message_after_cart_table() {

        $userid = get_current_user_id();
        $banning_type = FPRewardSystem::check_banning_type($userid);
        if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
            ?>
            <style type="text/css">
            <?php echo get_option('rs_cart_page_custom_css'); ?>
            </style>
            <?php
            global $woocommerce;
            global $coupon_code;
            if (is_user_logged_in()) {
                $user_ID = get_current_user_id();
                $getinfousernickname = get_user_by('id', $user_ID);
                $couponcodeuserlogin = $getinfousernickname->user_login;
                $minimum_cart_total_redeem = get_option('rs_minimum_cart_total_points');
                $cart_subtotal_for_redeem = $woocommerce->cart->subtotal;



                $cart_subtotal_redeem_amount = $cart_subtotal_for_redeem;
                $getinfousernickname = get_user_by('id', $user_ID);
                $couponcodeuserlogin = $getinfousernickname->user_login;
                $get_old_points = RSPointExpiry::get_sum_of_total_earned_points($user_ID);                
                if ($get_old_points > 0) {

                    $coupon_code = 'sumo_' . strtolower($couponcodeuserlogin); // Code
                    $coupon = new WC_Coupon($coupon_code);
                    $checkfirstimeredeem = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($user_ID, 'rsfirsttime_redeemed');
                    if ($checkfirstimeredeem != '1') {

                        if ($get_old_points >= get_option("rs_first_time_minimum_user_points")) {
                            if ($cart_subtotal_redeem_amount >= $minimum_cart_total_redeem) {

                                if (get_option('rs_redeem_field_type_option') == '1') {
                                    ?>
                                    <div class="fp_apply_reward">
                                        <?php if (get_option("rs_show_hide_redeem_caption") == '1') { ?>
                                            <label for="rs_apply_coupon_code_field"><?php echo get_option('rs_redeem_field_caption'); ?></label>
                                        <?php } ?>
                                        <?php
                                        if (get_option('rs_show_hide_redeem_placeholder') == '1') {
                                            $placeholder = get_option('rs_redeem_field_placeholder');
                                        }
                                        ?>
                                        <input id="rs_apply_coupon_code_field" class="input-text" type="text" placeholder="<?php echo $placeholder; ?>" value="" name="rs_apply_coupon_code_field">
                                        <input class="button <?php echo get_option('rs_extra_class_name_apply_reward_points'); ?>" type="submit" id='mainsubmi' value="<?php echo get_option('rs_redeem_field_submit_button_caption'); ?>" name="rs_apply_coupon_code">
                                    </div>
                                    <div class='rs_warning_message' style='display:inline-block;color:red'></div>
                                    <?php
                                }
                            } else {
                                if (get_option('rs_show_hide_minimum_cart_total_error_message') == '1') {
                                    $userid = get_current_user_id();
                                    $banning_type = FPRewardSystem::check_banning_type($userid);
                                    if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                        $min_cart_total_redeeming = get_option('rs_min_cart_total_redeem_error');
                                        $min_cart_amount_to_find = "[carttotal]";
                                        $min_cart_total_currency_to_find = "[currencysymbol]";
                                        $min_cart_amount_to_replace = get_option('rs_minimum_cart_total_points');
                                        $min_cart_total_currency_to_replace = self::get_woocommerce_formatted_price($min_cart_amount_to_replace);
                                        $min_cart_total_msg1 = str_replace($min_cart_amount_to_find, $min_cart_total_currency_to_replace, $min_cart_total_redeeming);
                                        $min_cart_total_replaced = str_replace($min_cart_total_currency_to_find, "", $min_cart_total_msg1);
                                        ?>
                                        <div class="woocommerce-info"><?php echo $min_cart_total_replaced; ?></div>
                                        <?php
                                    }
                                }
                            }
                        } else {
                            if (get_option('rs_show_hide_first_redeem_error_message') == '1') {
                                $userid = get_current_user_id();
                                $banning_type = FPRewardSystem::check_banning_type($userid);
                                if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                    $rs_first_redeem_message = get_option('rs_min_points_first_redeem_error_message');
                                    $rs_first_redeem_to_find = "[firstredeempoints]";
                                    $rs_first_redeem_to_replace = get_option('rs_first_time_minimum_user_points');
                                    $rs_first_redeem_replaced = str_replace($rs_first_redeem_to_find, $rs_first_redeem_to_replace, $rs_first_redeem_message);
                                    ?>

                                    <div class="woocommerce-info"><?php echo $rs_first_redeem_replaced; ?></div>
                                    <?php
                                }
                            }
                        }

                    } else {

                        if ($get_old_points >= get_option("rs_minimum_user_points_to_redeem")) {
                            if ($cart_subtotal_redeem_amount >= $minimum_cart_total_redeem) {

                                if (get_option('rs_redeem_field_type_option') == '1') {
                                    ?>
                                    <div class="fp_apply_reward">
                                        <?php if (get_option("rs_show_hide_redeem_caption") == '1') { ?>
                                            <label for="rs_apply_coupon_code_field"><?php echo get_option('rs_redeem_field_caption'); ?></label>
                                        <?php } ?>
                                        <?php
                                        if (get_option('rs_show_hide_redeem_placeholder') == '1') {
                                            $placeholder = get_option('rs_redeem_field_placeholder');
                                        }
                                        ?>
                                        <input id="rs_apply_coupon_code_field" class="input-text" type="text" placeholder="<?php echo $placeholder; ?>" value="" name="rs_apply_coupon_code_field">
                                        <input class="button <?php echo get_option('rs_extra_class_name_apply_reward_points'); ?>" type="submit" id='mainsubmi' value="<?php echo get_option('rs_redeem_field_submit_button_caption'); ?>" name="rs_apply_coupon_code">
                                    </div>
                                    <div class='rs_warning_message' style='display:inline-block;color:red'></div>
                                    <?php
                                }

                            } else {
                                if (get_option('rs_show_hide_minimum_cart_total_error_message') == '1') {
                                    $userid = get_current_user_id();
                                    $banning_type = FPRewardSystem::check_banning_type($userid);
                                    if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                        $min_cart_total_redeeming = get_option('rs_min_cart_total_redeem_error');
                                        $min_cart_amount_to_find = "[carttotal]";
                                        $min_cart_total_currency_to_find = "[currencysymbol]";
                                        $min_cart_amount_to_replace = get_option('rs_minimum_cart_total_points');
                                        $min_cart_total_currency_to_replace = self::get_woocommerce_formatted_price($min_cart_amount_to_replace);
                                        $min_cart_total_msg1 = str_replace($min_cart_amount_to_find, $min_cart_total_currency_to_replace, $min_cart_total_redeeming);
                                        $min_cart_total_replaced = str_replace($min_cart_total_currency_to_find, "", $min_cart_total_msg1);
                                        ?>
                                        <div class="woocommerce-info"><?php echo $min_cart_total_replaced; ?></div>
                                        <?php
                                    }
                                }
                            }
                        } else {
                            if (get_option('rs_show_hide_after_first_redeem_error_message') == '1') {
                                $userid = get_current_user_id();
                                $banning_type = FPRewardSystem::check_banning_type($userid);
                                if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                    $rs_minpoints_after_first_redeem = get_option('rs_min_points_after_first_error');
                                    $min_points_to_replace = get_option('rs_minimum_user_points_to_redeem');
                                    $min_points_to_find = "[points_after_first_redeem]";
                                    $min_points_after_first_replaced = str_replace($min_points_to_find, $min_points_to_replace, $rs_minpoints_after_first_redeem);
                                    ?>
                                    <div class="woocommerce-info"><?php echo $min_points_after_first_replaced; ?></div>
                                    <?php
                                }
                            }
                        }
                        ?>

                        <?php

                    }
                } else {
                    if (get_option('rs_show_hide_points_empty_error_message') == '1') {
                        $userid = get_current_user_id();
                        $banning_type = FPRewardSystem::check_banning_type($userid);
                        if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                            $user_points_empty_message = get_option('rs_current_points_empty_error_message');
                            ?>
                            <div class="woocommerce-info"><?php echo $user_points_empty_message; ?></div>
                            <?php
                        }
                    }
                }
            }
        }
    }


    public static function get_woocommerce_formatted_price($price) {
        if (function_exists('woocommerce_price')) {
            return woocommerce_price($price);
        } else {
            if (function_exists('wc_price')) {
                return wc_price($price);
            }
        }
    }
    /*Function to get the reward points to be displayed in message in cart and checkout*/
    public static function get_reward_points_to_display_msg_in_cart_and_checkout() {
        global $checkproduct;
        $userid = get_current_user_id();
        $banning_type = FPRewardSystem::check_banning_type($userid);
        if ($banning_type != 'earningonly' && $banning_type != 'both') {
            global $messageglobal;
            if (is_user_logged_in()) {
                global $totalrewardpointsnew;
                global $totalrewardpoints;
                $rewardpoints = array('0');
                $totalrewardpoints;
                global $woocommerce;
                global $value;
                $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                $global_reward_type = get_option('rs_global_reward_type');

                foreach ($woocommerce->cart->cart_contents as $key => $value) {
                    $cartquantity = $value['quantity'];
                    $rewardspoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_rewardsystempoints');
                    $checkenable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_rewardsystemcheckboxvalue');
                    $checkenablevariation = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['variation_id'], '_enable_reward_points');
                    $variablerewardpoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['variation_id'], '_reward_points');
                    $variationselectrule = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['variation_id'], '_select_reward_rule');
                    $variationrewardpercent = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['variation_id'], '_reward_percent');
                    $variable_product1 = new WC_Product_Variation($value['variation_id']);
                    if (get_option('rs_set_price_percentage_reward_points') == '1') {
                        $variationregularprice = $variable_product1->regular_price;
                    } else {
                        $variationregularprice = $variable_product1->price;
                    }

                    do_action_ref_array('rs_price_rule_checker_variant', array(&$variationregularprice, &$value));

                    $checkruleoption = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_rewardsystem_options');
                    $checkrewardpercent = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_rewardsystempercent');
                    if (get_option('rs_set_price_percentage_reward_points') == '1') {
                        $getregularprice = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_regular_price');
                    } else {
                        $getregularprice = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_price');
                    }

                    do_action_ref_array('rs_price_rule_checker_simple', array(&$getregularprice, &$value));

                    $user_ID = get_current_user_id();
                    $checkproduct = get_product($value['product_id']);
                    $checkanotherproduct = get_product($value['variation_id']);
                    if ($checkproduct->is_type('simple') || ($checkproduct->is_type('subscription'))) {
                        if ($checkenable == 'yes') {
                            if ($checkruleoption == '1') {
                                if ($rewardspoints == '') {
                                    $term = get_the_terms($value['product_id'], 'product_cat');
                                    if (is_array($term)) {
                                        $rewardpoints = array('0');
                                        foreach ($term as $term) {
                                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_reward_system_category', true);
                                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);
                                            if (($enablevalue == 'yes') && ($enablevalue != '')) {
                                                if ($display_type == '1') {
                                                    $checktermpoints = get_woocommerce_term_meta($term->term_id, 'rs_category_points', true);
                                                    if ($checktermpoints == '') {

                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $getregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = get_woocommerce_term_meta($term->term_id, 'rs_category_points', true);
                                                    }
                                                } else {
                                                    $pointconversion = get_option('rs_earn_point');
                                                    $pointconversionvalue = get_option('rs_earn_point_value');
                                                    $getaverage = get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) / 100;
                                                    $getaveragepoints = $getaverage * $getregularprice;
                                                    $pointswithvalue = $getaveragepoints * $pointconversion;
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $getregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            } else {
                                                if ($global_enable == '1') {
                                                    if ($global_reward_type == '1') {
                                                        $rewardpoints[] = get_option('rs_global_reward_points');
                                                    } else {
                                                        $pointconversion = get_option('rs_earn_point');
                                                        $pointconversionvalue = get_option('rs_earn_point_value');
                                                        $getaverage = get_option('rs_global_reward_percent') / 100;
                                                        $getaveragepoints = $getaverage * $getregularprice;
                                                        $pointswithvalue = $getaveragepoints * $pointconversion;
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($global_enable == '1') {
                                            if ($global_reward_type == '1') {
                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                            } else {
                                                $pointconversion = get_option('rs_earn_point');
                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                $getaveragepoints = $getaverage * $getregularprice;
                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                            }
                                        }
                                    }
                                    if (!empty($rewardpoints)) {
                                        $rewardspoints = max($rewardpoints);
                                    }
                                }
                                $totalrewardpoints = RSMemberFunction::user_role_based_reward_points(get_current_user_id(), $rewardspoints) * $cartquantity;

                                $totalrewardpointsnew[$value['product_id']] = $totalrewardpoints;
                            } else {
                                $pointconversion = get_option('rs_earn_point');
                                $pointconversionvalue = get_option('rs_earn_point_value');
                                $getaverage = $checkrewardpercent / 100;
                                $getaveragepoints = $getaverage * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                $points = $pointswithvalue / $pointconversionvalue;
                                if ($checkrewardpercent == '') {
                                    $term = get_the_terms($value['product_id'], 'product_cat');
                                    if (is_array($term)) {
                                        $rewardpoints = array('0');
                                        foreach ($term as $term) {

                                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_reward_system_category', true);
                                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);
                                            if (($enablevalue == 'yes') && ($enablevalue != '')) {
                                                if ($display_type == '1') {
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_points', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $getregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {

                                                        $rewardpoints[] = get_woocommerce_term_meta($term->term_id, 'rs_category_points', true);
                                                    }
                                                } else {
                                                    $pointconversion = get_option('rs_earn_point');
                                                    $pointconversionvalue = get_option('rs_earn_point_value');
                                                    $getaverage = get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) / 100;
                                                    $getaveragepoints = $getaverage * $getregularprice;
                                                    $pointswithvalue = $getaveragepoints * $pointconversion;
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $getregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            } else {
                                                if ($global_enable == '1') {
                                                    if ($global_reward_type == '1') {
                                                        $rewardpoints[] = get_option('rs_global_reward_points');
                                                    } else {
                                                        $pointconversion = get_option('rs_earn_point');
                                                        $pointconversionvalue = get_option('rs_earn_point_value');
                                                        $getaverage = get_option('rs_global_reward_percent') / 100;
                                                        $getaveragepoints = $getaverage * $getregularprice;
                                                        $pointswithvalue = $getaveragepoints * $pointconversion;
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($global_enable == '1') {
                                            if ($global_reward_type == '1') {
                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                            } else {
                                                $pointconversion = get_option('rs_earn_point');
                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                $getaveragepoints = $getaverage * $getregularprice;
                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                            }
                                        }
                                    }
                                    $points = max($rewardpoints);
                                }

                                $totalrewardpoints = RSMemberFunction::user_role_based_reward_points(get_current_user_id(), $points) * $cartquantity;
                                $totalrewardpointsnew[$value['product_id']] = $totalrewardpoints;
                            }
                        }
                    } else {
                        if ($checkenablevariation == '1') {
                            if ($variationselectrule == '1') {
                                $parentvariationid = new WC_Product_Variation($value['variation_id']);
                                $newparentid = $parentvariationid->parent->id;
                                if ($variablerewardpoints == '') {
                                    $term = get_the_terms($newparentid, 'product_cat');
                                    if (is_array($term)) {
                                        $rewardpoints = array('0');
                                        foreach ($term as $term) {

                                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_reward_system_category', true);
                                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);
                                            if (($enablevalue == 'yes') && ($enablevalue != '')) {
                                                if ($display_type == '1') {
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_points', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = get_woocommerce_term_meta($term->term_id, 'rs_category_points', true);
                                                    }
                                                } else {
                                                    $pointconversion = get_option('rs_earn_point');
                                                    $pointconversionvalue = get_option('rs_earn_point_value');
                                                    $getaverage = get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) / 100;
                                                    $getaveragepoints = $getaverage * $variationregularprice;
                                                    $pointswithvalue = $getaveragepoints * $pointconversion;
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            } else {
                                                if ($global_enable == '1') {
                                                    if ($global_reward_type == '1') {
                                                        $rewardpoints[] = get_option('rs_global_reward_points');
                                                    } else {
                                                        $pointconversion = get_option('rs_earn_point');
                                                        $pointconversionvalue = get_option('rs_earn_point_value');
                                                        $getaverage = get_option('rs_global_reward_percent') / 100;
                                                        $getaveragepoints = $getaverage * $variationregularprice;
                                                        $pointswithvalue = $getaveragepoints * $pointconversion;
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($global_enable == '1') {
                                            if ($global_reward_type == '1') {
                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                            } else {
                                                $pointconversion = get_option('rs_earn_point');
                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                            }
                                        }
                                    }
                                    $variablerewardpoints = max($rewardpoints);
                                }
                                $totalrewardpoints = RSMemberFunction::user_role_based_reward_points(get_current_user_id(), $variablerewardpoints) * $cartquantity;
                                $totalrewardpointsnew[$value['variation_id']] = $totalrewardpoints;
                            } else {
                                $pointconversion = get_option('rs_earn_point');
                                $pointconversionvalue = get_option('rs_earn_point_value');
                                $getaverage = $variationrewardpercent / 100;
                                $getaveragepoints = $getaverage * $variationregularprice;
                                $getpointsvalue = $getaveragepoints * $pointconversion;
                                $points = $getpointsvalue / $pointconversionvalue;
                                $parentvariationid = new WC_Product_Variation($value['variation_id']);
                                $newparentid = $parentvariationid->parent->id;
                                if ($variationrewardpercent == '') {
                                    $term = get_the_terms($newparentid, 'product_cat');
                                    if (is_array($term)) {
                                        $rewardpoints = array('0');
                                        foreach ($term as $term) {

                                            $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_reward_system_category', true);
                                            $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);

                                            if (($enablevalue == 'yes') && ($enablevalue != '')) {
                                                if ($display_type == '1') {
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_points', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = get_woocommerce_term_meta($term->term_id, 'rs_category_points', true);
                                                    }
                                                } else {
                                                    $pointconversion = get_option('rs_earn_point');
                                                    $pointconversionvalue = get_option('rs_earn_point_value');
                                                    $getaverage = get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) / 100;
                                                    $getaveragepoints = $getaverage * $variationregularprice;
                                                    $pointswithvalue = $getaveragepoints * $pointconversion;
                                                    if (get_woocommerce_term_meta($term->term_id, 'rs_category_percent', true) == '') {
                                                        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                                                        $global_reward_type = get_option('rs_global_reward_type');
                                                        if ($global_enable == '1') {
                                                            if ($global_reward_type == '1') {
                                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                                            } else {
                                                                $pointconversion = get_option('rs_earn_point');
                                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                            }
                                                        }
                                                    } else {
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            } else {
                                                if ($global_enable == '1') {
                                                    if ($global_reward_type == '1') {
                                                        $rewardpoints[] = get_option('rs_global_reward_points');
                                                    } else {
                                                        $pointconversion = get_option('rs_earn_point');
                                                        $pointconversionvalue = get_option('rs_earn_point_value');
                                                        $getaverage = get_option('rs_global_reward_percent') / 100;
                                                        $getaveragepoints = $getaverage * $variationregularprice;
                                                        $pointswithvalue = $getaveragepoints * $pointconversion;
                                                        $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($global_enable == '1') {
                                            if ($global_reward_type == '1') {
                                                $rewardpoints[] = get_option('rs_global_reward_points');
                                            } else {
                                                $pointconversion = get_option('rs_earn_point');
                                                $pointconversionvalue = get_option('rs_earn_point_value');
                                                $getaverage = get_option('rs_global_reward_percent') / 100;
                                                $getaveragepoints = $getaverage * $variationregularprice;
                                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                                $rewardpoints[] = $pointswithvalue / $pointconversionvalue;
                                            }
                                        }
                                    }
                                    $points = max($rewardpoints);

                                }

                                $totalrewardpoints = RSMemberFunction::user_role_based_reward_points(get_current_user_id(), $points) * $cartquantity;

                                $totalrewardpointsnew[$value['variation_id']] = $totalrewardpoints;
                            }
                        }
                    }
                    if ($checkproduct->is_type('simple') || ($checkproduct->is_type('subscription'))) {
                        if ($checkenable == 'yes') {
                            $validrewardpoints = do_shortcode('[rspoint]');
                            if ($validrewardpoints > 0) {
                                $messageglobal[$value['product_id']] = do_shortcode(get_option('rs_message_product_in_cart')) . "<br>";
                            }
                        }
                    } else {
                        $validrewardpoints = do_shortcode('[rspoint]');
                        if ($validrewardpoints > 0) {
                            if ($checkenablevariation == '1') {
                                $messageglobal[$value['variation_id']] = do_shortcode(get_option('rs_message_product_in_cart')) . "<br>";
                            }
                        }
                    }
                }
                ?>

                <?php
            }
        }
    }
    
    public static function check_the_applied_coupons(){
        global $woocommerce;
        if(get_option('rs_enable_redeem_for_order') == 'yes'){
            if (is_array($woocommerce->cart->get_applied_coupons())) {
                $getappliedcoupon = $woocommerce->cart->get_applied_coupons();
                if(!empty($getappliedcoupon)){
                    $currentuserid = get_current_user_id();
                    $user_ID = get_current_user_id();
                    $getinfousernickname = get_user_by('id', $user_ID);
                    $couponcodeuserlogin = $getinfousernickname->user_login;
                    $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");
                    foreach ($woocommerce->cart->get_applied_coupons() as $coupons){
                        if (strtolower($coupons) == $usernickname) {
                            return true;
                        }else{
                            return false;
                        }
                    }
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }

    public static function display_msg_in_cart_page() {
        global $woocommerce;
        global $value;
        global $totalrewardpointsnew;
        global $messageglobal;    
        $checkenableoption = self::check_the_applied_coupons();
        if (get_option('rs_show_hide_message_for_each_products') == '1') {
            if($checkenableoption == false){
                if (is_array($totalrewardpointsnew)) {
                    if (array_sum($totalrewardpointsnew) > 0) {
                        ?>
                        <div class="woocommerce-info">
                            <?php
                            if (is_array($messageglobal)) {
                                foreach ($messageglobal as $globalcommerce) {
                                    echo $globalcommerce;
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            }            
        }
    }

    public static function display_msg_in_cart_page_for_balance_reward_points() {
        global $woocommerce;
        if (is_user_logged_in()) {
            if (get_option('rs_show_hide_message_for_redeem_points') == '1') {                
                if (is_array($woocommerce->cart->get_applied_coupons())) {
                    $currentuserid = get_current_user_id();
                    $user_ID = get_current_user_id();
                    $getinfousernickname = get_user_by('id', $user_ID);
                    $couponcodeuserlogin = $getinfousernickname->user_login;
                    $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");
                    if (isset($woocommerce->cart->coupon_discount_amounts["$usernickname"])) {
                        $total = $woocommerce->cart->coupon_discount_amounts["$usernickname"];
                        if ($total != 0) {
                            foreach ($woocommerce->cart->get_applied_coupons() as $coupons) {
                                if (strtolower($coupons) == $usernickname) {
                                    $userid = get_current_user_id();
                                    $banning_type = FPRewardSystem::check_banning_type($userid);
                                    if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                        ?>
                                        <div class="woocommerce-message">
                                            <?php echo do_shortcode(get_option('rs_message_user_points_redeemed_in_cart')); ?>
                                        </div>                                        
                                        <?php
                                        /*Error Message to be Displayed When the order contain only redeeming*/                                        
                                        if(get_option('rs_enable_redeem_for_order') == 'yes'){                                        
                                            if(isset($_POST['rs_apply_coupon_code'])){
                                                ?>
                                                <div class="woocommerce-info">
                                                    <?php echo get_option('rs_errmsg_for_redeeming_in_order'); ?>
                                                </div>                                        
                                                <?php
                                                                                                
                                            }
                                        }
                                        if (get_option('rs_redeem_field_type_option') == '2') {
                                            ?>
                                            <script type="text/javascript">
                                                jQuery(document).ready(function () {
                                                    jQuery("#mainsubmi").parent().hide();
                                                });</script>
                                            <?php
                                        }                                                                                
                                    }
                                    ?>
                                    <?php
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public static function display_msg_in_checkout_page() {
        global $woocommerce;
        global $value;
        global $totalrewardpointsnew;
        global $messageglobal;
        $checkenableoption = self::check_the_applied_coupons();
        if (get_option('rs_show_hide_message_for_each_products_checkout_page') == '1') {
            if($checkenableoption == false){
                if (is_array($totalrewardpointsnew)) {
                    if (array_sum($totalrewardpointsnew) > 0) {
                        ?>

                        <div class="woocommerce-info">
                            <?php
                            if (is_array($messageglobal)) {
                                foreach ($messageglobal as $globalcommerce) {
                                    echo $globalcommerce;
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            }            
        }
    }

    public static function get_redeem_point_to_display_in_msg() {
        global $woocommerce;
        global $value;
        $user_ID = get_current_user_id();
        $getinfousernickname = get_user_by('id', $user_ID);
        $couponcodeuserlogin = $getinfousernickname->user_login;

        $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");        
            $total = WC()->session->get('fp_rs_redeem_amount');
            $current_conversion = get_option('rs_redeem_point');
            $point_amount = get_option('rs_redeem_point_value');
            $newtotal = $total * $current_conversion;
            $newtotal = $newtotal / $point_amount;

            $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
            return round($newtotal, $roundofftype);        
    }
    
    public static function display_msg_in_checkout_page_for_balance_reward_points() {
        global $woocommerce;
        if (get_option('rs_show_hide_message_for_redeem_points_checkout_page') == '1') {
            if (is_user_logged_in()) {
                if (is_array($woocommerce->cart->get_applied_coupons())) {
                    $currentuserid = get_current_user_id();

                    $user_ID = get_current_user_id();
                    $getinfousernickname = get_user_by('id', $user_ID);
                    $couponcodeuserlogin = $getinfousernickname->user_login;

                    $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");
                    if (isset($woocommerce->cart->coupon_discount_amounts["$usernickname"])) {
                        $total = $woocommerce->cart->coupon_discount_amounts["$usernickname"];
                        if ($total != 0) {
                            foreach ($woocommerce->cart->get_applied_coupons() as $coupons) {
                                if (strtolower($coupons) == $usernickname) {

                                    $userid = get_current_user_id();
                                    $banning_type = FPRewardSystem::check_banning_type($userid);
                                    if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                                        ?>
                                        <div class="woocommerce-message">
                                            <?php echo do_shortcode(get_option('rs_message_user_points_redeemed_in_checkout')); ?>
                                        </div>
                                        <?php
                                        /*Error Message to be Displayed When the order contain only redeeming*/                                        
                                        if(get_option('rs_enable_redeem_for_order') == 'yes'){                                        
                                            if(isset($_POST['rs_apply_coupon_code'])){
                                                ?>
                                                <div class="woocommerce-info">
                                                    <?php echo get_option('rs_errmsg_for_redeeming_in_order'); ?>
                                                </div>                                        
                                                <?php                                                                                                
                                            }
                                        }
                                        
                                        if (get_option('rs_redeem_field_type_option') == '2') {
                                            ?>
                                            <script type="text/javascript">
                                                jQuery(document).ready(function () {
                                                    jQuery("#mainsubmi").parent().hide();
                                                });</script>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <?php
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public static function get_each_product_price_in_cart() {
        global $totalrewardpoints;
        global $checkproduct;
        global $value;
        if ($checkproduct->is_type('simple') || ($checkproduct->is_type('subscription'))) {
            if (RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['product_id'], '_rewardsystemcheckboxvalue') != 'yes') {
                return "<strong>0</strong>";
            } else {
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                return round($totalrewardpoints, $roundofftype);
            }
        } else {
            if (RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($value['variation_id'], '_enable_reward_points') != '1') {
                return "<strong>0</strong>";
            } else {
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                return round($totalrewardpoints, $roundofftype);
            }
        }
    }
    
    public static function get_each_producttitle_in_cart() {
        global $checkproduct;
        global $value;
        if ($checkproduct->is_type('simple') || ($checkproduct->is_type('subscription'))) {
            return "<strong>" . get_the_title($value['product_id']) . "</strong>";
        } else if ($checkproduct->is_type('variable') || ($checkproduct->is_type('variable-subscription'))) {
            return "<strong>" . get_the_title($value['variation_id']) . "</strong>";
        }
    }
    
    public static function get_each_product_points_value_in_cart() {
        $getpoints = do_shortcode('[rspoint]');
        $redeemconver = $getpoints / get_option('rs_redeem_point');
        $updatedvalue = $redeemconver * get_option('rs_redeem_point_value');
        $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
        return self::get_woocommerce_formatted_price(round($updatedvalue, $roundofftype));
    }
    
    public static function get_balance_redeem_points_to_display_in_msg() {
        global $woocommerce;

        $currentuserid = get_current_user_id();

        $user_ID = get_current_user_id();
        
        $getinfousernickname = get_user_by('id', $user_ID);
        $couponcodeuserlogin = $getinfousernickname->user_login;
        $usernickname = 'sumo_' . strtolower("$couponcodeuserlogin");        
            $total = WC()->session->get('fp_rs_redeem_amount');
            $current_conversion = get_option('rs_redeem_point');
            $point_amount = get_option('rs_redeem_point_value');
            $total = $total * $current_conversion;
            $total = $total / $point_amount;

            $user_ID = get_current_user_id();
            $myrewardpoint = RSPointExpiry::get_sum_of_total_earned_points($user_ID);
            $majorpoint = $myrewardpoint - $total;

            $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
            
            $roundofftype =  round($majorpoint, $roundofftype);
            if($roundofftype <=0){
                return 0;
            }else{
                return $roundofftype;
            }        
    }
    
    public static function validation_in_my_cart() {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('#mainsubmi').click(function () {
                    var float_value_current_points = parseFloat('<?php $currentuserpoints = RSPointExpiry::get_sum_of_total_earned_points(get_current_user_id()); echo $currentuserpoints; ?>');
                    float_value_current_points = Math.round(float_value_current_points *100)/100;                    
                    var float_value_minimum_redeeming_points = parseFloat('<?php echo get_option("rs_minimum_redeeming_points"); ?>');
                    float_value_minimum_redeeming_points = Math.round(float_value_minimum_redeeming_points *100)/100;   
                    var float_value_maximum_redeeming_points = parseFloat('<?php echo get_option("rs_maximum_redeeming_points"); ?>');
                    float_value_maximum_redeeming_points = Math.round(float_value_maximum_redeeming_points *100)/100;   
                  <?php if(get_option('rs_redeem_field_type_option') == '1'){ ?>
                       var getvalue = jQuery('#rs_apply_coupon_code_field').val();
                    if (getvalue === '') {
                       jQuery('.rs_warning_message').html('<?php echo addslashes(get_option('rs_redeem_empty_error_message')); ?>');
                        return false;
                    } else if (jQuery.isNumeric(getvalue) == false) {
                        jQuery('.rs_warning_message').html('<?php echo addslashes(get_option('rs_redeem_character_error_message')); ?>');
                        return false;
                    } else if (getvalue > float_value_current_points) {
                        jQuery('.rs_warning_message').html('<?php echo addslashes(get_option('rs_redeem_max_error_message')); ?>');
                        return false;
                    } else if (jQuery.isNumeric(getvalue) == true) {
                        if (getvalue < 0) {
                            jQuery('.rs_warning_message').html('<?php echo addslashes(get_option('rs_redeem_character_error_message')); ?>');
                            return false;
                        }
                    }
        <?php if (get_option('rs_minimum_redeeming_points') == (get_option('rs_maximum_redeeming_points'))) { ?>
                       if (getvalue < float_value_minimum_redeeming_points){
                          jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_and_maximum_redeem_point_error_message"))); ?>');
                           return false;
                       }
                          else if (getvalue > float_value_maximum_redeeming_points){ 
                          jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_and_maximum_redeem_point_error_message"))); ?>');
                          return false;
                    }
        <?php } ?>
        <?php if (get_option('rs_minimum_redeeming_points') != '') { ?>
                        if (getvalue < float_value_minimum_redeeming_points) {
                            jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_redeem_point_error_message"))); ?>');
                            return false;
                        }
        <?php } ?>
        
        <?php if (get_option('rs_maximum_redeeming_points') != '') { ?>
                       if (getvalue > float_value_maximum_redeeming_points) {
                           jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_maximum_redeem_point_error_message"))); ?>');
                           return false;
                       }
        <?php } ?>

   <?php } else{?>

      
         var getvalue = jQuery('#rs_apply_coupon_code_field').val();

        <?php if (get_option('rs_minimum_redeeming_points') == (get_option('rs_maximum_redeeming_points'))) { ?>
                       if (getvalue < float_value_minimum_redeeming_points){
                           jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_and_maximum_redeem_point_error_message_for_buttontype"))); ?>');
                           return false;
                       }
                          else if (getvalue > float_value_maximum_redeeming_points){ 
                          jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_and_maximum_redeem_point_error_message_for_buttontype"))); ?>');
                          return false;
                    }
        <?php } ?>
        <?php if (get_option('rs_minimum_redeeming_points') != '') { ?>
                        if (getvalue < float_value_minimum_redeeming_points) {
                            jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_minimum_redeem_point_error_message_for_button_type"))); ?>');
                            return false;
                        }
        <?php } ?>
        
        <?php if (get_option('rs_maximum_redeeming_points') != '') { ?>
                       if (getvalue > float_value_maximum_redeeming_points) {
                           jQuery('.rs_warning_message').html('<?php echo do_shortcode(addslashes(get_option("rs_maximum_redeem_point_error_message_for_button_type"))); ?>');
                           return false;
                       }
        <?php } ?> 

                    <?php } ?>
                });
            });
       </script>
        <?php
    }
    
    public static function get_minimum_redeeming_points_value() {
        return get_option('rs_minimum_redeeming_points');
    }
    
     public static function get_maximum_redeeming_points_value() {
        return get_option('rs_maximum_redeeming_points');
    }
   
    public static function get_minimum_and_maximum_redeeming_points_value() {
        return get_option('rs_minimum_redeeming_points');
    }
    
    public static function display_redeem_points_buttons_on_cart_page() {        
        global $woocommerce;
        if(is_user_logged_in()){    
            $userid = get_current_user_id();
            $banning_type = FPRewardSystem::check_banning_type($userid);
            if ($banning_type != 'redeemingonly' && $banning_type != 'both') {
                $getuserid = get_current_user_id();
                $user_current_points = RSPointExpiry::get_sum_of_total_earned_points($getuserid);
                if ($user_current_points > 0) {
                    if (get_user_meta($getuserid, 'rsfirsttime_redeemed', true) != '1') {                    
                        if ($user_current_points >= get_option("rs_first_time_minimum_user_points")) {
                            if (get_option('rs_show_hide_redeem_field') == '1') {
                                if (get_option('rs_redeem_field_type_option') == '2') {                                
                                    $getuserid = get_current_user_id();
                                    $user_current_points = RSPointExpiry::get_sum_of_total_earned_points($getuserid);                                
                                    $current_carttotal_amount = $woocommerce->cart->subtotal;                                                     
                                    $redeem_conversion = get_option('rs_redeem_point');
                                    $current_carttotal_in_points = $current_carttotal_amount * $redeem_conversion;                                 
                                    $limitation_percentage_for_redeeming = get_option('rs_percentage_cart_total_redeem');
                                    $updated_points_step1 = $current_carttotal_in_points / 100;
                                    $updated_points_for_redeeming = $updated_points_step1 * $limitation_percentage_for_redeeming;                                 
                                    $cartpoints_string_to_replace = "[cartredeempoints]";
                                    $currency_symbol_string_to_find = "[currencysymbol]";
                                    $cuurency_value_string_to_find = "[pointsvalue]";
                                    if ($user_current_points >= $updated_points_for_redeeming) {                                    
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message');
                                        $percentage_string_to_replace = "[redeempercent]";
                                        $cuurency_value_string_to_find = "[pointsvalue]";
                                        $points_conversion_value = get_option('rs_redeem_point_value');
                                        $points_currency_value = $updated_points_for_redeeming / $redeem_conversion;
                                        $points_currency_amount_to_replace =$updated_points_for_redeeming;
                                        $points_for_redeeming = $updated_points_for_redeeming /$points_conversion_value;
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message');
                                        $currency_symbol_string_to_replace = self::get_woocommerce_formatted_price($points_currency_value);
                                        $redeem_button_message_replaced_first = str_replace($cuurency_value_string_to_find, $currency_symbol_string_to_replace, $redeem_button_message_more);
                                        $redeem_button_message_replaced_second = str_replace($currency_symbol_string_to_find, "", $redeem_button_message_replaced_first);
                                        $redeem_button_message_replaced_third = str_replace($cartpoints_string_to_replace, $points_for_redeeming, $redeem_button_message_replaced_second);
                                    } else {

                                        $points_for_redeeming = $user_current_points;
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message');
                                        $points_conversion_value = get_option('rs_redeem_point_value');
                                        $points_currency_value = $points_for_redeeming / $redeem_conversion;                                    
                                        $points_currency_amount_to_replace = $points_currency_value * $points_conversion_value;
                                        $currency_symbol_string_to_replace = self::get_woocommerce_formatted_price($points_currency_value);
                                        $redeem_button_message_replaced_first = str_replace($currency_symbol_string_to_find,"" , $redeem_button_message_more);
                                        $redeem_button_message_replaced_second = str_replace($cuurency_value_string_to_find, $currency_symbol_string_to_replace, $redeem_button_message_replaced_first);
                                        $redeem_button_message_replaced_third = str_replace($cartpoints_string_to_replace, $points_for_redeeming, $redeem_button_message_replaced_second);
                                    }
                                    $minimum_cart_total_redeem = get_option('rs_minimum_cart_total_points');
                                    $cart_subtotal_for_redeem = $woocommerce->cart->get_cart_subtotal();
                                    $cart_subtotal_redeem_amount = preg_replace('/[^0-9\.]+/', '', $cart_subtotal_for_redeem);
                                    if ($cart_subtotal_redeem_amount >= $minimum_cart_total_redeem) {
                                        ?>
                                        <form method="post">
                                            <div class="woocommerce-info"><?php echo $redeem_button_message_replaced_third; ?>
                                                <input id="rs_apply_coupon_code_field" class="input-text" type="hidden"  value="<?php echo $points_for_redeeming; ?> " name="rs_apply_coupon_code_field">
                                                <input class="button <?php echo get_option('rs_extra_class_name_apply_reward_points'); ?>" type="submit" id='mainsubmi' value="<?php echo get_option('rs_redeem_field_submit_button_caption'); ?>" name="rs_apply_coupon_code">
                                            </div>
                                        </form>
                                        <?php
                                    }
                                }
                            }
                        }
                    } else {
                        if ($user_current_points >= get_option("rs_minimum_user_points_to_redeem")) {
                            if (get_option('rs_show_hide_redeem_field') == '1') {
                                if (get_option('rs_redeem_field_type_option') == '2') {
                                    $getuserid = get_current_user_id();
                                    $user_current_points = RSPointExpiry::get_sum_of_total_earned_points($getuserid);
                                    $current_carttotal_amount = $woocommerce->cart->subtotal;
                                    $redeem_conversion = get_option('rs_redeem_point');
                                    $current_carttotal_in_points = $current_carttotal_amount * $redeem_conversion;
                                    $limitation_percentage_for_redeeming = get_option('rs_percentage_cart_total_redeem');
                                    $updated_points_step1 = $current_carttotal_in_points / 100;
                                    $updated_points_for_redeeming = $updated_points_step1 * $limitation_percentage_for_redeeming;                                
                                    $cartpoints_string_to_replace = "[cartredeempoints]";
                                    $currency_symbol_string_to_find = "[currencysymbol]";
                                    $cuurency_value_string_to_find = "[pointsvalue]";
                                    if ($user_current_points >= $updated_points_for_redeeming) {
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message');                                    
                                        $cuurency_value_string_to_find = "[pointsvalue]";
                                        $points_conversion_value = get_option('rs_redeem_point_value');
                                        $points_currency_value = $updated_points_for_redeeming / $redeem_conversion;
                                        $points_currency_amount_to_replace = $updated_points_for_redeeming;
                                        $points_for_redeeming = $updated_points_for_redeeming/$points_conversion_value;
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message');
                                        $currency_symbol_string_to_replace = self::get_woocommerce_formatted_price($points_currency_value);

                                        $redeem_button_message_replaced_first = str_replace($cuurency_value_string_to_find, $currency_symbol_string_to_replace, $redeem_button_message_more);
                                        $redeem_button_message_replaced_second = str_replace($currency_symbol_string_to_find, "", $redeem_button_message_replaced_first);
                                        $redeem_button_message_replaced_third = str_replace($cartpoints_string_to_replace, $points_for_redeeming, $redeem_button_message_replaced_second);
                                    } else {
                                        $points_for_redeeming = $user_current_points;
                                        $redeem_button_message_more = get_option('rs_redeeming_button_option_message_checkout');
                                        $points_conversion_value = get_option('rs_redeem_point_value');
                                        $points_currency_value = $points_for_redeeming / $redeem_conversion;                                    
                                        $points_currency_amount_to_replace = $points_currency_value * $points_conversion_value;
                                        $currency_symbol_string_to_replace = self::get_woocommerce_formatted_price($points_currency_value);
                                        $redeem_button_message_replaced_first = str_replace($currency_symbol_string_to_find, "", $redeem_button_message_more);
                                        $redeem_button_message_replaced_second = str_replace($cuurency_value_string_to_find, $currency_symbol_string_to_replace, $redeem_button_message_replaced_first);
                                        $redeem_button_message_replaced_third = str_replace($cartpoints_string_to_replace, $points_for_redeeming, $redeem_button_message_replaced_second);
                                    }
                                    $minimum_cart_total_redeem = get_option('rs_minimum_cart_total_points');
                                    $cart_subtotal_for_redeem = $woocommerce->cart->get_cart_subtotal();
                                    $cart_subtotal_redeem_amount = preg_replace('/[^0-9\.]+/', '', $cart_subtotal_for_redeem);
                                    if ($cart_subtotal_redeem_amount >= $minimum_cart_total_redeem) {
                                        ?>
                                        <form method="post">
                                            <div class="woocommerce-info"><?php echo $redeem_button_message_replaced_third; ?>
                                                <input id="rs_apply_coupon_code_field" class="input-text" type="hidden"  value="<?php echo $points_for_redeeming; ?> " name="rs_apply_coupon_code_field">           
                                                <input class="button <?php echo get_option('rs_extra_class_name_apply_reward_points'); ?>" type="submit" id='mainsubmi' value="<?php echo get_option('rs_redeem_field_submit_button_caption'); ?>" name="rs_apply_coupon_code" />
                                                 <div class='rs_warning_message' style='display:inline-block;color:red'></div>
                                            </div>
                                        </form>
                                        <?php
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public static function change_coupon_label($link, $coupon) {    
        $userid = get_current_user_id();
        $banning_type = FPRewardSystem::check_banning_type($userid);
        if ($banning_type != 'earningonly' && $banning_type != 'both') {
            $couponcode = $coupon->code;
            if (is_string($coupon))
                $coupon = new WC_Coupon($coupon);
            $user_ID = get_current_user_id();
            $getinfousernickname = get_user_by('id', $user_ID);
            $couponcodeuserlogin = $getinfousernickname->user_login;
            if (strtolower($couponcode) == 'sumo_' . strtolower($couponcodeuserlogin)) {
                $newcoupon = get_option('rs_coupon_label_message');
                $link = ' ' . $newcoupon;
            }    
        }
        return $link;
    }    
}

new RSFunctionForCart();