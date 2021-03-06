<?php

class RSBookingCompatibility {

    public function __construct() {
        add_action('wp_head', array($this, 'booking_compatible'));
        add_action('wp_ajax_woocommerce_booking_sumo_reward_system', array($this, 'sumo_compatible_with_booking'));        
        add_shortcode('sumobookingpoints', array($this, 'sumo_fixed_points_compatible_with_booking'));
        add_action('woocommerce_before_single_product', array($this, 'add_woocommerce_notice'));
        add_action('woocommerce_before_cart', array($this, 'reward_points_in_top_of_content'));
        add_shortcode('bookingrspoint', array($this, 'get_each_product_price'));
        add_action('woocommerce_before_cart', array($this, 'rewardmessage_in_cart'));
        add_filter('woocommerce_rewardsystem_messages_settings', array($this, 'add_custom_field_to_message_tab'));
        add_shortcode('bookingproducttitle', array($this, 'get_woocommerce_booking_product_title'));
        include_once('rs_update_booking_points.php');        
    }

    public static function booking_compatible() {
        if (class_exists('WC_Bookings')) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    var xhr;
                    jQuery('.woocommerce_booking_variations').hide();
                    jQuery('#wc-bookings-booking-form').on('change', 'input, select', function () {
                        if (xhr)
                            xhr.abort();
                        var form = jQuery(this).closest('form');                        
                        var dataparam = ({
                            action: 'woocommerce_booking_sumo_reward_system',
                            form: form.serialize(),
                        });
                        jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", dataparam, function (response) {

                            if ((response.sumorewardpoints !== 0) && (response.sumorewardpoints !== '')) {
                                jQuery('.woocommerce_booking_variations').addClass('woocommerce-info');
                                jQuery('.woocommerce_booking_variations').show();
                                jQuery('.sumobookingpoints').html(response.sumorewardpoints);
                            } else {
                                jQuery('.woocommerce_booking_variations').hide();
                            }
                        }, 'json');
                    });                   
                });
            </script>

            <?php
        }
    }

    public static function sumo_fixed_points_compatible_with_booking() {
        global $post;
        $booking_id = $post->ID;
        $getproducttype = get_product($booking_id);
        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
        $global_reward_type = get_option('rs_global_reward_type');
        if ($getproducttype->is_type('booking')) {
            $enablerewards = get_post_meta($post->ID, '_rewardsystemcheckboxvalue', true);
            $getaction = get_post_meta($post->ID, '_rewardsystem_options', true);
            $getpoints = get_post_meta($post->ID, '_rewardsystempoints', true);
            $getpercent = get_post_meta($post->ID, '_rewardsystempercent', true);

            $rewardpoints = array('0');
            if ($enablerewards == 'yes') {
                if ($getaction == '1') {
                    if ($getpoints == '') {
                        $term = get_the_terms($post->ID, 'product_cat');
                        if (is_array($term)) {
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

                                            if ($global_enable == '1') {
                                                if ($global_reward_type == '1') {
                                                    $rewardpoints[] = get_option('rs_global_reward_points');
                                                } else {

                                                }
                                            }
                                        } else {

                                        }
                                    }
                                } else {
                                    if ($global_enable == '1') {
                                        if ($global_reward_type == '1') {
                                            $rewardpoints[] = get_option('rs_global_reward_points');
                                        } else {

                                        }
                                    }
                                }
                            }
                        } else {
                            if ($global_enable == '1') {
                                if ($global_reward_type == '1') {
                                    $rewardpoints[] = get_option('rs_global_reward_points');
                                } else {

                                }
                            }
                        }
                        if (!empty($rewardpoints)) {
                            $getpoints = max($rewardpoints);
                        }
                    }
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    return round($getpoints, $roundofftype);
                }
            }
        }
    }

    public static function sumo_compatible_with_booking() {
        $posted = array();
        parse_str($_POST['form'], $posted);
        $booking_id = $posted['add-to-cart'];
        $product = get_product($booking_id);
        if (!$product) {
            die(json_encode(array('sumorewardpoints' => 0)));
        }
        $booking_form = new WC_Booking_Form($product);
        $cost = $booking_form->calculate_booking_cost($posted);
        if (is_wp_error($cost)) {
            die(json_encode(array('sumorewardpoints' => 0)));
        }
        $tax_display_mode = get_option('woocommerce_tax_display_shop');
        $display_price = $tax_display_mode == 'incl' ? $product->get_price_including_tax(1, $cost) : $product->get_price_excluding_tax(1, $cost);

        if (get_option('timezone_string') != '') {
            $timezonedate = date_default_timezone_set(get_option('timezone_string'));
        } else {
            $timezonedate = date_default_timezone_set('UTC');
        }
        global $post;
        $checkproducttype = get_product($booking_id);
        $global_enable = get_option('rs_global_enable_disable_sumo_reward');
        $global_reward_type = get_option('rs_global_reward_type');

        if ($checkproducttype->is_type('booking')) {
            $enablerewards = get_post_meta($booking_id, '_rewardsystemcheckboxvalue', true);
            $getaction = get_post_meta($booking_id, '_rewardsystem_options', true);
            $getpoints = get_post_meta($booking_id, '_rewardsystempoints', true);
            $getpercent = get_post_meta($booking_id, '_rewardsystempercent', true);

            $getregularprice = $display_price;

            $rewardpoints = array('0');
            if ($enablerewards == 'yes') {
                if ($getaction == '1') {
                    if ($getpoints == '') {
                        $term = get_the_terms($booking_id, 'product_cat');
                        if (is_array($term)) {
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
                            $getpoints = max($rewardpoints);
                        }
                    }
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    $finalpoints = round($getpoints, $roundofftype);
                    die(json_encode(array('sumorewardpoints' => $finalpoints, 'booking_price' => $display_price, 'booking_formatted_price' => woocommerce_price($display_price) . $product->get_price_suffix())));
                } else {
                    $points = get_option('rs_earn_point');
                    $pointsequalto = get_option('rs_earn_point_value');
                    $takeaverage = $getpercent / 100;
                    $mainaveragevalue = $takeaverage * $getregularprice;
                    $addinpoint = $mainaveragevalue * $points;
                    $totalpoint = $addinpoint / $pointsequalto;
                    if ($getpercent === '') {
                        $term = get_the_terms($booking_id, 'product_cat');
                        if (is_array($term)) {
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
                        if (!empty($rewardpoints)) {
                            $totalpoint = max($rewardpoints);
                        } else {
                            $totalpoint = 0;
                        }
                    }
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    $finalpoints = round($totalpoint, $roundofftype);
                    die(json_encode(array('sumorewardpoints' => $finalpoints, 'booking_price' => $display_price, 'booking_formatted_price' => woocommerce_price($display_price) . $product->get_price_suffix())));
                }
            }
        }
    }

    public static function add_woocommerce_notice() {
        global $post;
        $order = '';        
        if (is_user_logged_in()) {
            $userid = get_current_user_id();
            $banning_type = FPRewardSystem::check_banning_type($userid);
            if ($banning_type != 'earningonly' && $banning_type != 'both') {
                $checkproducttype = get_product($post->ID);
                if ($checkproducttype->is_type('booking')) {
                    if (get_post_meta($post->ID, '_rewardsystemcheckboxvalue', true) == 'yes') {
                        if (get_post_meta($post->ID, '_rewardsystem_options', true) == '1') {
                            $rewardpoints = do_shortcode('[sumobookingpoints]');
                            if ($rewardpoints > 0) {
                                ?>
                                <div class="woocommerce-info"><?php _e("Book this Product and Earn <span class='sumobookingpoints'>$rewardpoints</span> Points"); ?></div>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="woocommerce_booking_variations"><?php _e("Book this Product and Earn <span class='sumobookingpoints'></span> Points"); ?></div>
                            <?php
                        }
                    }
                }
            }
        } else {
            $checkproducttype = get_product($post->ID);
            if ($checkproducttype->is_type('booking')) {
                if (get_post_meta($post->ID, '_rewardsystemcheckboxvalue', true) == 'yes') {
                    if (get_post_meta($post->ID, '_rewardsystem_options', true) == '1') {
                        $rewardpoints = do_shortcode('[sumobookingpoints]');
                        if ($rewardpoints > 0) {
                            ?>
                            <div class="woocommerce-info"><?php _e("Book this Product and Earn <span class='sumobookingpoints'>$rewardpoints</span> Points"); ?></div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="woocommerce_booking_variations"><?php _e("Book this Product and Earn <span class='sumobookingpoints'></span> Points"); ?></div>
                        <?php
                    }
                }
            }
        }
    }   

    public static function reward_points_in_top_of_content() {
        global $checkproduct;      
        if (is_user_logged_in()) {
            $userid = get_current_user_id();
            $banning_type = FPRewardSystem::check_banning_type($userid);
            if ($banning_type != 'earningonly' && $banning_type != 'both') {
                global $messageglobalbooking;
                global $totalrewardpointsnewbooking;
                global $totalrewardpoints;
                $rewardpoints = array('0');
                $totalrewardpoints;
                global $woocommerce;
                global $bookingvalue;
                $global_enable = get_option('rs_global_enable_disable_sumo_reward');
                $global_reward_type = get_option('rs_global_reward_type');
                foreach ($woocommerce->cart->cart_contents as $key => $bookingvalue) {

                    $cartquantity = $bookingvalue['quantity'];
                    $rewardspoints = get_post_meta($bookingvalue['product_id'], '_rewardsystempoints', true);
                    $checkenable = get_post_meta($bookingvalue['product_id'], '_rewardsystemcheckboxvalue', true);
                    $checkenablevariation = get_post_meta($bookingvalue['variation_id'], '_enable_reward_points', true);
                    $variablerewardpoints = get_post_meta($bookingvalue['variation_id'], '_reward_points', true);
                    $variationselectrule = get_post_meta($bookingvalue['variation_id'], '_select_reward_rule', true);
                    $variationrewardpercent = get_post_meta($bookingvalue['variation_id'], '_reward_percent', true);
                    $variable_product1 = new WC_Product_Variation($bookingvalue['variation_id']);
                    if (get_option('rs_set_price_percentage_reward_points') == '1') {
                        $variationregularprice = $variable_product1->regular_price;
                    } else {
                        $variationregularprice = $variable_product1->price;
                    }
                    $checkruleoption = get_post_meta($bookingvalue['product_id'], '_rewardsystem_options', true);
                    $checkrewardpercent = get_post_meta($bookingvalue['product_id'], '_rewardsystempercent', true);
                    $getregularprice = $bookingvalue['data']->price;
                    $user_ID = get_current_user_id();
                    $checkproduct = get_product($bookingvalue['product_id']);
                    $checkanotherproduct = get_product($bookingvalue['variation_id']);
                    if ($checkproduct->is_type('booking')) {
                        if ($checkenable == 'yes') {
                            if ($checkruleoption == '1') {
                                if ($rewardspoints == '') {
                                    $term = get_the_terms($bookingvalue['product_id'], 'product_cat');
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
                                $totalrewardpoints = $rewardspoints * $cartquantity;
                                $totalrewardpointsnewbooking[$bookingvalue['product_id']] = $totalrewardpoints;
                            } else {
                                $pointconversion = get_option('rs_earn_point');
                                $pointconversionvalue = get_option('rs_earn_point_value');
                                $getaverage = $checkrewardpercent / 100;
                                $getaveragepoints = $getaverage * $getregularprice;
                                $pointswithvalue = $getaveragepoints * $pointconversion;
                                $points = $pointswithvalue / $pointconversionvalue;
                                if ($checkrewardpercent == '') {
                                    $term = get_the_terms($bookingvalue['product_id'], 'product_cat');
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

                                $totalrewardpoints = $points * $cartquantity;
                                $totalrewardpointsnewbooking[$bookingvalue['product_id']] = $totalrewardpoints;
                            }
                        }
                    } else {

                    }
                    if ($checkproduct->is_type('booking')) {
                        if ($checkenable == 'yes') {
                            $validrewardpoints = do_shortcode('[bookingrspoint]');
                            if ($validrewardpoints > 0) {
                                $messageglobalbooking[] = do_shortcode(get_option('rs_woocommerce_booking_product_cart_message')) . "<br>";
                            }
                        }
                    }
                }
                ?>
                <?php
            }
        }
    }

    public static function get_each_product_price() {
        global $totalrewardpoints;
        global $checkproduct;
        global $bookingvalue;
        if ($checkproduct->is_type('booking')) {
            if (get_post_meta($bookingvalue['product_id'], '_rewardsystemcheckboxvalue', true) != 'yes') {
                return "<strong>0</strong>";
            } else {
                $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                return round($totalrewardpoints, $roundofftype);
            }
        }
    }

    public static function rewardmessage_in_cart() {
        global $woocommerce;
        global $bookingvalue;
        global $totalrewardpointsnewbooking;
        global $messageglobalbooking;
        if (get_option('rs_show_hide_message_for_each_products') == '1') {
            if (is_array($totalrewardpointsnewbooking)) {
                if (array_sum($totalrewardpointsnewbooking) > 0) {
                    ?>
                    <div class="woocommerce-info">
                        <?php
                        if (is_array($messageglobalbooking)) {
                            foreach ($messageglobalbooking as $globalcommerce) {
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

    public static function get_data_from_cart_item() {
        global $woocommerce;
        $cart_item = $woocommerce->cart->cart_contents;
        if (class_exists('WC_Bookings_Cart')) {
            ?>
            <div class="woocommerce-info">
                <?php                
                foreach ($cart_item as $bookingvalue) {
                    
                }
                ?>

            </div>
            <?php
        }
    }

    public static function add_custom_field_to_message_tab($settings) {
        $updated_settings = array();
        foreach ($settings as $section) {
            if (isset($section['id']) && '_rs_reward_messages' == $section['id'] &&
                    isset($section['type']) && 'sectionend' == $section['type']) {
                $updated_settings[] = array(
                    'name' => __('Enter Cart Message for WooCommerce Booking Product', 'rewardsystem'),
                    'desc' => __('Please Enter Cart Message for WooCommerce Booking Product', 'rewardsystem'),
                    'tip' => '',
                    'id' => 'rs_woocommerce_booking_product_cart_message',
                    'css' => 'min-width:550px;',
                    'std' => 'Purchase this [bookingproducttitle] and Earn [bookingrspoint] Reward Point',
                    'type' => 'textarea',
                    'newids' => 'rs_woocommerce_booking_product_cart_message',
                    'desc_tip' => true,
                );
            }
            $updated_settings[] = $section;
        }

        return $updated_settings;
    }

    public static function get_woocommerce_booking_product_title() {
        global $checkproduct;
        global $bookingvalue;
        if ($checkproduct->is_type('booking')) {
            return "<strong>" . get_the_title($bookingvalue['product_id']) . "</strong>";
        }
    }   

}

new RSBookingCompatibility();
