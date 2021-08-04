<?php

class RSFunctionForOrder {

    public function __construct() {

        add_action('admin_head', array($this, 'rs_show_hide_in_order'));

        add_action('woocommerce_order_items_table', array($this, 'display_total_redem_points_order'));

        add_action('woocommerce_email_after_order_table', array($this, 'get_the_total_earned_points_for_order'));

        add_action('woocommerce_email_after_order_table', array($this, 'get_the_total_redeem_points_for_order'));

        add_action('add_meta_boxes', array($this, 'add_meta_box_for_earned'));

        add_filter('woocommerce_get_formatted_order_total', array($this, 'display_total_point_price'), 10, 2);
        add_filter('woocommerce_order_formatted_line_subtotal', array($this, 'display_line_total'), 8, 3);
    }

    public function display_line_total($line_total1, $id, $item) {

        $rewardgateway = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($item->id, '_payment_method');

        if ($rewardgateway == 'reward_gateway') {

            $labelpoint = get_option('rs_label_for_point_value');
            if (function_exists('get_product')) {
                $gettheproducts = get_product($id['product_id']);               
                 $product_id = $id['variation_id'] != 0 ? $id['variation_id'] :$id['product_id'];                                    
                 $points_array = RSFunctionForCart::calculate_point_price_for_products($product_id);
                 $points = (int)implode(",", $points_array);                       
                if ($gettheproducts->is_type('variable')) {                    
                    $enablevariable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
                    if ($enablevariable == '1') {                                                    
                            $quantity = $id['qty'];
                            $getpoints = $points * $quantity;
                             if ($points > 0) {

                            $replace = str_replace("/", "", $labelpoint);
                            echo $replace . $points;
                        } else {
                            echo $line_total1;
                        }                         
                    }
                }
                
                $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
                
                
                 if ($enable == 'yes') {                        
                        $quantity = $id['qty'];
                     
                        $getpoints = $points * $quantity;
                        if ($points > 0) {

                            $replace = str_replace("/", "", $labelpoint);                            
                            return $replace . $points;
                        } else {
                            return $line_total1;
                            
                        }
                  
                }  else {
                    return $line_total1;
                } 
            }
            
        } else {
            return $line_total1;
        }
        
    }

    public function display_total_point_price($order1, $orderids) {        
        $rewardgateway = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($orderids->id, '_payment_method');

        if ($rewardgateway == 'reward_gateway') {

            global $woocommerce;
            $total1 = 0;
            $totalpoints1 = 0;
            $totalpoints2 = 0;
            $totalvariable = 0;
            $points = array();
            $total = array();
            $varpoints = array();
            $total11 = array();
            $order = new WC_Order($orderids);


            $enablepoint = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($orderids->id, 'pointsvalue');
            if ($enablepoint == '1') {
                foreach ($order->get_items()as $item) {
                    $product_id = $item['variation_id'] != 0 ? $item['variation_id'] :$item['product_id'];                                    
                    $points_array = RSFunctionForCart::calculate_point_price_for_products($product_id);
                    $points = (int)implode(",", $points_array);  
                    
                    $enablevariable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_enable_reward_points_price');
                    $opto1[] = $enablevariable;
                    if ($enablevariable == '1') {
                        $quantity = $item['qty'];
                        $getpoints = $points * $quantity;                        
                        $varpoints[] = $getpoints;
                        
                        $total11[] = $item['line_total'];
                    }
                    $enable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($product_id, '_rewardsystem_enable_point_price');
                    $opto[] = $enable;                    
                    if ($enable == 'yes') {      
                        $quantity = $item['qty'];
                        $points_simple[] = $points * $quantity;                        
                        $total[] = $item['line_total'];
                    }
                }


                $totalpoints2 = array_sum($varpoints);
                
                $totalpoints1 = array_sum($points_simple) + $totalpoints2;
                
                $totalvariable = array_sum($total11);
                $total1 = array_sum($total) + $totalvariable;
                $total2 = $order->get_total();
                if ($totalpoints1 > 0) {
                    $total3 = $total2 - $total1;
                } else {
                    $total3 = $total2;
                }
               
                
                $labelpoint = get_option('rs_label_for_point_value');
               
                $replace = str_replace("/", "", $labelpoint);
                if ($totalpoints1 > 0) {
                    return $replace . $totalpoints1;
                } else {
                    return $order1;
                }
            } else {
                return $order1;
            }
        } else {
            return $order1;
        }
    }

    public static function rs_show_hide_in_order() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {

                if (jQuery('#rs_enable_msg_for_earned_points').is(":checked")) {
                    jQuery('#rs_msg_for_earned_points').parent().parent().show();
                } else {
                    jQuery('#rs_msg_for_earned_points').parent().parent().hide();
                }

                jQuery('#rs_enable_msg_for_earned_points').change(function () {
                    if (jQuery('#rs_enable_msg_for_earned_points').is(":checked")) {
                        jQuery('#rs_msg_for_earned_points').parent().parent().show();
                    } else {
                        jQuery('#rs_msg_for_earned_points').parent().parent().hide();
                    }
                });

                if (jQuery('#rs_enable_msg_for_redeem_points').is(":checked")) {
                    jQuery('#rs_msg_for_redeem_points').parent().parent().show();
                } else {
                    jQuery('#rs_msg_for_redeem_points').parent().parent().hide();
                }

                jQuery('#rs_enable_msg_for_redeem_points').change(function () {
                    if (jQuery('#rs_enable_msg_for_redeem_points').is(":checked")) {
                        jQuery('#rs_msg_for_redeem_points').parent().parent().show();
                    } else {
                        jQuery('#rs_msg_for_redeem_points').parent().parent().hide();
                    }
                });
            });
        </script>
        <?php
    }

    public static function display_total_redem_points_order() {
        if (get_option('rs_show_hide_total_points_order_field') == '1') {
            $total_points = WC()->session->get('rewardpoints');
            if ($total_points != 0) {
                ?> 
                <tfoot>
                    <tr class="cart-total">
                        <th><?php echo get_option('rs_total_earned_point_caption_checkout'); ?></th>
                        <td><?php echo $total_points; ?></td>
                    </tr>
                </tfoot>


                <?php
            }
        }
    }

    /* To get the total earned for order */

    public static function get_the_total_earned_points_for_order($order) {
        $status = get_option('rs_order_status_control');
        global $wpdb;
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        $orderid = $order->id;
        $gettotalearnpoints = $wpdb->get_results("SELECT earnedpoints FROM $table_name WHERE orderid=$orderid", ARRAY_A);
        $orderstatus = $order->post_status;
        if (is_array($status)) {
            foreach ($status as $statuses) {
                $statusstr = $statuses;
            }
        }
        $replacestatus = str_replace('wc-completed', $statusstr, $orderstatus);
        if (get_option('rs_enable_msg_for_earned_points') == 'yes') {
            if (in_array($replacestatus, $status)) {
                $totalearnedvalue = "";
                $earned_total = $gettotalearnpoints;
                if (is_array($earned_total)) {
                    foreach ($earned_total as $key => $value) {
                        $totalearnedvalue+=$value['earnedpoints'];
                    }
                    $msgforearnedpoints = get_option('rs_msg_for_earned_points');
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    $replacemsgforearnedpoints = str_replace('[earnedpoints]', $totalearnedvalue != "" ? round($totalearnedvalue, $roundofftype) : "0", $msgforearnedpoints);
                    echo '<br><br>' . '<b>' . $replacemsgforearnedpoints . '<b>' . '<br><br>';
                }
            }
        }
    }

    /* To get the total redeem for order */

    public static function get_the_total_redeem_points_for_order($order) {
        $status = get_option('rs_order_status_control');
        global $wpdb;
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        $orderid = $order->id;
        $gettotalredeempoints = $wpdb->get_results("SELECT redeempoints FROM $table_name WHERE orderid=$orderid", ARRAY_A);
        $orderstatus = $order->post_status;
        if (is_array($status)) {
            foreach ($status as $statuses) {
                $statusstr = $statuses;
            }
        }
        $replacestatus = str_replace('wc-completed', $statusstr, $orderstatus);
        if (get_option('rs_enable_msg_for_redeem_points') == 'yes') {
            if (in_array($replacestatus, $status)) {
                $totalredeemvalue = "";
                $redeem_total = $gettotalredeempoints;
                if (is_array($redeem_total)) {
                    foreach ($redeem_total as $key => $value) {
                        $totalredeemvalue+=$value['redeempoints'];
                    }
                    $msgforredeempoints = get_option('rs_msg_for_redeem_points');
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    $replacemsgforredeempoints = str_replace('[redeempoints]', $totalredeemvalue != "" ? round($totalredeemvalue, $roundofftype) : "0", $msgforredeempoints);
                    echo '<b>' . $replacemsgforredeempoints . '</b>';
                }
            }
        }
    }

    public static function add_meta_box_for_earned() {
        add_meta_box('order_earned_points', 'Earned Point and Redeem Points For Current Order', array('RSFunctionForOrder', 'add_meta_box_to_earned_and_redeem_points'), 'shop_order', 'normal', 'low');
    }

    public static function add_meta_box_to_earned_and_redeem_points($order) {

        $order = $_GET['post'];
        global $wpdb;
        $earned_totals = '';
        $redeem_totals = '';
        $totalearnedvalue = "";
        $totalredeemvalue = '';
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        $getcurrentuserid = $order;
        $gettotalearnpoints = $wpdb->get_results("SELECT earnedpoints,redeempoints FROM $table_name WHERE orderid=$getcurrentuserid", ARRAY_A);
        foreach ($gettotalearnpoints as $gettotalearnpointss) {
            $earned_totals+= $gettotalearnpointss['earnedpoints'];
            $redeem_totals+= $gettotalearnpointss['redeempoints'];
        }
        if (get_option('rs_enable_msg_for_earned_points') == 'yes') {
            if (get_option('rs_enable_msg_for_redeem_points') == 'yes') {
                $totalearnedvalue = $earned_totals;
                $totalredeemvalue = $redeem_totals;

                $msgforearnedpoints = get_option('rs_msg_for_earned_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforearnedpoints = str_replace('[earnedpoints]', $totalearnedvalue != "" ? round($totalearnedvalue, $roundofftype) : "0", $msgforearnedpoints);

                $msgforredeempoints = get_option('rs_msg_for_redeem_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforredeempoints = str_replace('[redeempoints]', $totalredeemvalue != "" ? round($totalredeemvalue, $roundofftype) : "0", $msgforredeempoints);
            } else {
                $totalearnedvalue = $earned_totals;

                $msgforearnedpoints = get_option('rs_msg_for_earned_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforearnedpoints = str_replace('[earnedpoints]', $totalearnedvalue != "" ? round($totalearnedvalue, $roundofftype) : "0", $msgforearnedpoints);

                $msgforredeempoints = get_option('rs_msg_for_redeem_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforredeempoints = str_replace('[redeempoints]', $totalredeemvalue != "" ? round($totalredeemvalue, $roundofftype) : "0", $msgforredeempoints);
            }
        } else {

            $msgforearnedpoints = get_option('rs_msg_for_earned_points');
            $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
            $replacemsgforearnedpoints = str_replace('[earnedpoints]', $totalearnedvalue != "" ? round($totalearnedvalue, $roundofftype) : "0", $msgforearnedpoints);

            if (get_option('rs_enable_msg_for_redeem_points') == 'yes') {

                $totalredeemvalue = $redeem_totals;

                $msgforredeempoints = get_option('rs_msg_for_redeem_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforredeempoints = str_replace('[redeempoints]', $totalredeemvalue != "" ? round($totalredeemvalue, $roundofftype) : "0", $msgforredeempoints);
            } else {
                $msgforredeempoints = get_option('rs_msg_for_redeem_points');
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                $replacemsgforredeempoints = str_replace('[redeempoints]', $totalredeemvalue != "" ? round($totalredeemvalue, $roundofftype) : "0", $msgforredeempoints);
            }
        }
        ?>
        <table width="100%" style=" border-radius: 10px; border-style: solid; border-color: #dfdfdf;">
            <tr><td style="text-align:center; background-color:#F1F1F1"><h3>Earned Points</h3></td><td style="text-align:center;background-color:#F1F1F1"><h3>Redeem Points</h3></td></tr>
            <tr><td style="text-align:center"><?php echo $replacemsgforearnedpoints; ?></td><td style="text-align:center"><?php echo $replacemsgforredeempoints; ?></td></tr>
        </table>


        <?php
    }

}

new RSFunctionForOrder();
