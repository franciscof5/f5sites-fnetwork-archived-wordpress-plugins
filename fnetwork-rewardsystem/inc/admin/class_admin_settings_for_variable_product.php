<?php

class RSVariableProduct {
    
    public function __construct() {
              
        add_action('woocommerce_product_after_variable_attributes', array($this, 'rs_admin_option_for_variable_product'), 10, 3);
        
        add_action('woocommerce_product_after_variable_attributes_js', array($this, 'rs_admin_option_for_variable_product_in_js'));  
        
        add_action('woocommerce_save_product_variation', array($this, 'save_variable_product_fields'), 10, 2);

        add_action('woocommerce_process_product_meta_variable-subscription', array($this, 'save_variable_product_fields_for_subscription'), 10, 1);
        
        add_action('woocommerce_before_single_product', array($this, 'display_msg_for_variable_product'));
        
        add_shortcode('variationrewardpoints', array($this, 'add_variation_shortcode_div'));
        
        add_action('wp_head', array($this, 'display_purchase_msg_for_variable_product'));
        
        add_shortcode('variationpointsvalue', array($this, 'add_variation_point_values_shortcode'));
        
        add_action('wp_ajax_nopriv_getvariationid', array($this, 'add_shortcode_for_rewardpoints_of_variation'));

        add_action('wp_ajax_getvariationid', array($this, 'add_shortcode_for_rewardpoints_of_variation'));

    }                 
    
    public static function rs_admin_option_for_variable_product_in_js() {
        ?>
        <table>
             <tr>
                <td>
        <?php
        // Select
        woocommerce_wp_select(
                array(
                    'id' => '_enable_reward_points_price[ + loop + ]',
                    'label' => __('Enable SUMO Reward Points Price', 'rewardsystem'),
                    'desc_tip' => 'true',
                    'description' => __('Choose an Option.', 'rewardsystem'),
                    'value' => $variation_data['_enable_reward_points_price'][0],
                    'options' => array(
                        '1' => __('Enable', 'rewardsystem'),
                        '2' => __('Disable', 'rewardsystem'),
                    )
                )
        );
        ?>
                </td>
            </tr>


            <tr>
                <td>
                    <?php
                    // Text Field
                    woocommerce_wp_text_input(
                            array(
                                'id' => 'price_points[ + loop + ]',
                                'label' => __(' Points Price:', 'rewardsystem'),
                                'placeholder' => '',
                                'size' => '5',
                                'desc_tip' => 'true',
                                'description' => __('Point Price', 'rewardsystem'),
                                'value' => ''
                            )
                    );
                    ?>
                </td>
            </tr>
        <tr>
            <td>
                <?php
                // Select
                woocommerce_wp_select(
                        array(
                            'id' => '_enable_reward_points[ + loop + ]',
                            'label' => __('Enable SUMO Reward Points', 'rewardsystem'),
                            'desc_tip'=> 'true',
                            'description' => __('Choose an Option.', 'rewardsystem'),
                            'value' => $variation_data['_enable_reward_points'][0],
                            'options' => array(
                                '1' => __('Enable', 'rewardsystem'),
                                '2' => __('Disable', 'rewardsystem'),
                            )
                        )
                );
                ?>
            </td>
        </tr>

        <tr>
            <td>
                <?php
                // Select

                woocommerce_wp_select(
                        array(
                            'id' => '_select_reward_rule[ + loop + ]',
                            'label' => __('Reward Type', 'rewardsystem'),
                            'class' => '_select_reward_rule',
                            'description' => __('Select Reward Rule', 'rewardsystem'),
                            'value' => '',
                            'options' => array(
                                '1' => __('By Fixed Reward Points', 'rewardsystem'),
                                '2' => __('By Percentage of Product Price', 'rewardsystem'),
                            )
                        )
                );
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php
                // Text Field
                woocommerce_wp_text_input(
                        array(
                            'id' => '_reward_points[ + loop + ]',
                            'label' => __('Reward Points', 'rewardsystem'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('This Value is applicable for "By Fixed Reward Points" Reward Type', 'rewardsystem'),
                            'value' => ''
                        )
                );
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php
                woocommerce_wp_text_input(
                        array(
                            'id' => '_reward_percent[ + loop + ]',
                            'label' => __('Reward Percent', 'rewardsystem'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('This Value is applicable for "By Percentage of Product Price" Reward Type', 'rewardsystem'),
                            'value' => ''
                        )
                );
                ?>
            </td>
        </tr>

        <tr>
            <td>
                <?php
                // Select

                woocommerce_wp_select(
                        array(
                            'id' => '_select_referral_reward_rule[ + loop + ]',
                            'label' => __('Referral Reward Type', 'rewardsystem'),
                            'class' => '_select_referral_reward_rule',
                            'description' => __('Select Referral Reward Rule', 'rewardsystem'),
                            'value' => '',
                            'options' => array(
                                '1' => __('By Fixed Reward Points', 'rewardsystem'),
                                '2' => __('By Percentage of Product Price', 'rewardsystem'),
                            )
                        )
                );
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php
                // Text Field
                woocommerce_wp_text_input(
                        array(
                            'id' => '_referral_reward_points[ + loop + ]',
                            'label' => __('Referral Reward Points', 'rewardsystem'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('This Value is applicable for "By Fixed Reward Points" Referral Referral Reward Type', 'rewardsystem'),
                            'value' => ''
                        )
                );
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php
                woocommerce_wp_text_input(
                        array(
                            'id' => '_referral_reward_percent[ + loop + ]',
                            'label' => __('Referral Reward Percent', 'rewardsystem'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('This Value is applicable for "By Percentage of Product Price" Referral Reward Type', 'rewardsystem'),
                            'value' => ''
                        )
                );
                ?>
            </td>
        </tr>
        </table>
        <?php
    }
    
    public static function rs_admin_option_for_variable_product($loop, $variation_data, $variations) {
        global $post;
        global $woocommerce;
        $enable_reward_point = '';
        $reward_type = '';
        $reward_points ='';
        $reward_points_in_percent = '';
        $referral_reward_type = '';
        $referral_reward_points = '';
        $referral_reward_points_in_percent = '';
          $pointprice = '';
                    $enablepointprice = '';

        $variation_data = get_post_meta($variations->ID);

                    if (isset($variation_data['_enable_reward_points_price'][0]))
                        $enablepointprice = $variation_data['_enable_reward_points_price'][0];


                    woocommerce_wp_select(
                            array(
                                'id' => '_enable_reward_points_price[' . $loop . ']',
                                'label' => __('Enable Point Price:', 'rewardsystem'),
                                'desc_tip' => true,
                                'description' => __('Enable Point Price ', 'rewardsystem'),
                                'value' => $enablepointprice,
                                'default' => '1',
                                'options' => array(
                                    '1' => __('Enable', 'rewardsystem'),
                                    '2' => __('Disable', 'rewardsystem'),
                                )
                            )
                    );

                    if (isset($variation_data['price_points'][0]))
                        $pointprice = $variation_data['price_points'][0];


                    woocommerce_wp_text_input(
                            array(
                                'id' => 'price_points[' . $loop . ']',
                                'label' => __(' PointPrice:', 'rewardsystem'),
                                'size' => '5',
                                'value' => $pointprice,
                            )
                    );
        if (isset($variation_data['_enable_reward_points'][0]))
            $enable_reward_point = $variation_data['_enable_reward_points'][0];


        woocommerce_wp_select(
                array(
                    'id' => '_enable_reward_points[' . $loop . ']',
                    'label' => __('Enable SUMO Reward Points', 'rewardsystem'),
                    'default' => '2',
                    'desc_tip' => false,
                    'description' => __('Enable will Turn On Reward Points for Product Purchase and Category/Product Settings will be considered if it is available. '
                            . 'Disable will Turn Off Reward Points for Product Purchase and Category/Product Settings will be considered if it is available. ', 'rewardsystem'),
                    'value' => $enable_reward_point,
                    'options' => array(
                        '1' => __('Enable', 'rewardsystem'),
                        '2' => __('Disable', 'rewardsystem'),
                    )
                )
        );
        if (isset($variation_data['_select_reward_rule'][0]))
            $reward_type = $variation_data['_select_reward_rule'][0];


        woocommerce_wp_select(
                array(
                    'id' => '_select_reward_rule[' . $loop . ']',
                    'label' => __('Reward Type', 'rewardsystem'),
                    'default' => '2',
                    'value' => $reward_type,
                    'options' => array(
                        '1' => __('By Fixed Reward Points', 'rewardsystem'),
                        '2' => __('By Percentage of Product Price', 'rewardsystem'),
                    )
                )
        );

        if (isset($variation_data['_reward_points'][0]))
            $reward_points = $variation_data['_reward_points'][0];

        woocommerce_wp_text_input(
                array(
                    'id' => '_reward_points[' . $loop . ']',
                    'label' => __('Reward Points', 'rewardsystem'),
                    'description' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                            . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                    'desc_tip' => 'true',
                    'value' => $reward_points
                )
        );

        if (isset($variation_data['_reward_percent'][0]))
            $reward_points_in_percent = $variation_data['_reward_percent'][0];

        woocommerce_wp_text_input(
                array(
                    'id' => '_reward_percent[' . $loop . ']',
                    'label' => __('Reward Points in Percent %', 'rewardsystem'),
                    'description' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                            . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                    'desc_tip' => 'true',
                    'value' => $reward_points_in_percent
                )
        );

        if (isset($variation_data['_select_referral_reward_rule'][0]))
            $referral_reward_type = $variation_data['_select_referral_reward_rule'][0];


        woocommerce_wp_select(
                array(
                    'id' => '_select_referral_reward_rule[' . $loop . ']',
                    'label' => __('Referral Reward Type', 'rewardsystem'),
                    'default' => '2',
                    'value' => $referral_reward_type,
                    'options' => array(
                        '1' => __('By Fixed Reward Points', 'rewardsystem'),
                        '2' => __('By Percentage of Product Price', 'rewardsystem'),
                    )
                )
        );

        if (isset($variation_data['_referral_reward_points'][0]))
            $referral_reward_points = $variation_data['_referral_reward_points'][0];

        woocommerce_wp_text_input(
                array(
                    'id' => '_referral_reward_points[' . $loop . ']',
                    'label' => __('Referral Reward Points', 'rewardsystem'),
                    'description' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                            . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                    'desc_tip' => 'true',
                    'value' => $referral_reward_points
                )
        );

        if (isset($variation_data['_referral_reward_percent'][0]))
            $referral_reward_points_in_percent = $variation_data['_referral_reward_percent'][0];

        woocommerce_wp_text_input(
                array(
                    'id' => '_referral_reward_percent[' . $loop . ']',
                    'label' => __('Referral Reward Points in Percent %', 'rewardsystem'),
                    'description' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                            . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                    'desc_tip' => 'true',
                    'value' => $referral_reward_points_in_percent
                )
        );
    }

    /*
     * @ Save the Reward Points custom fields value of Variable Product in the product meta
     * 
     */  
    
   public static  function add_shortcode_for_rewardpoints_of_variation_point($variation_id) {
        
        $checkenable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variation_id, '_enable_reward_points_price');        
        $getpoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($variation_id, 'price_points');

        $global_enable = get_option('rs_local_enable_disable_point_price_for_product');


        if ($checkenable == '1') {
            
            $variable_product1 = new WC_Product_Variation($variation_id);
            
            $newparentid = $variable_product1->parent->id;

            if ($getpoints == '') {                
                $term = get_the_terms($newparentid, 'product_cat');
                if (is_array($term)) {

                    foreach ($term as $term) {
                        $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_point_price_category', true);

                        if (($enablevalue == 'yes') && ($enablevalue != '')) {

                            if (get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true) == '') {
                                if ($global_enable == '1') {
                                    if (get_option('rs_local_price_points_for_product') != '') {
                                        $rewardpoints = get_option('rs_local_price_points_for_product');
                                    } else {
                                        $rewardpoints = '';
                                    }
                                }
                            } else {
                                $rewardpoints = get_woocommerce_term_meta($term->term_id, 'rs_category_points_price', true);
                            }
                        } else {
                            if ($global_enable == '1') {

                                $rewardpoints = get_option('rs_local_price_points_for_product');
                            }
                        }
                    }
                } else {                    
                    
                    if ($global_enable == '1') {                        
                        if (get_option('rs_local_price_points_for_product') != '') {
                            $rewardpoints = get_option('rs_local_price_points_for_product');
                        } else {
                            $rewardpoints = '';
                        }
                    }else{
                        $rewardpoints = '';
                    }
                }

                $getpoints = $rewardpoints;
                return $getpoints;
            } else {
                return $getpoints;
            }
        }
    }
    
    
    //Save Variable Product Meta for Subscription Products
    public static function save_variable_product_fields_for_subscription($post_id) {
        if (isset($_POST['variable_sku'])) :
            $variable_sku = $_POST['variable_sku'];
            $variable_post_id = $_POST['variable_post_id'];

// Text Field
            $_text_field = $_POST['_reward_points'];
            for ($i = 0; $i < sizeof($variable_sku); $i++) :
                $variation_id = (int) $variable_post_id[$i];
                if (isset($_text_field[$i])) {
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_reward_points', stripslashes($_text_field[$i]));                   
                }
            endfor;
$point_select = $_POST['_enable_reward_points_price'];
                        for ($i = 0; $i < sizeof($variable_sku); $i++):
                            $variation_id = (int) $variable_post_id[$i];
                            if (isset($point_select[$i])) {
                                RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points_price', stripslashes($point_select[$i]));
                            }
                        endfor;

                        $point_text = $_POST['price_points'];
                        for ($i = 0; $i < sizeof($variable_sku); $i++):
                            $variation_id = (int) $variable_post_id[$i];
                            if (isset($point_text[$i])) {
                                RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, 'price_points', stripslashes($point_text[$i]));
                            }
                        endfor;
            
            $percent_text_field = $_POST['_reward_percent'];
            for ($i = 0; $i < sizeof($variable_sku); $i++):
                $variation_id = (int) $variable_post_id[$i];
                if (isset($percent_text_field[$i])) {                    
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_reward_percent', stripslashes($percent_text_field[$i]));
                }
            endfor;
//select
            $new_select = $_POST['_select_reward_rule'];
            for ($i = 0; $i < sizeof($variable_sku); $i++):
                $variation_id = (int) $variable_post_id[$i];
                if (isset($new_select[$i])) {                    
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_select_reward_rule', stripslashes($new_select[$i]));
                }
            endfor;


            $_text_fields = $_POST['_referral_reward_points'];
            for ($i = 0; $i < sizeof($variable_sku); $i++) :
                $variation_id = (int) $variable_post_id[$i];
                if (isset($_text_field[$i])) {                    
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_referral_reward_points', stripslashes($_text_fields[$i]));
                }
            endfor;

            $percent_text_fields = $_POST['_referral_reward_percent'];
            for ($i = 0; $i < sizeof($variable_sku); $i++):
                $variation_id = (int) $variable_post_id[$i];
                if (isset($percent_text_field[$i])) {                    
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_referral_reward_percent', stripslashes($percent_text_fields[$i]));
                }
            endfor;
//select
            $new_selects = $_POST['_select_referral_reward_rule'];
            for ($i = 0; $i < sizeof($variable_sku); $i++):
                $variation_id = (int) $variable_post_id[$i];
                if (isset($new_select[$i])) {                    
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_select_referral_reward_rule', stripslashes($new_selects[$i]));
                }
            endfor;


// Select
            $_select = $_POST['_enable_reward_points'];
            for ($i = 0; $i < sizeof($variable_sku); $i++) :
                $variation_id = (int) $variable_post_id[$i];
                if (isset($_select[$i])) {
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points', stripslashes($_select[$i]));
                    RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points', stripslashes($_select[$i]));
                }
            endfor;
        endif;
    }
    
    
    public static function save_variable_product_fields($variation_id, $i) {
        $variable_sku = $_POST['variable_sku'];
        $variable_post_id = $_POST['variable_post_id'];
        
        // Text Field
        $_text_field = $_POST['_reward_points'];
        
        if (isset($_text_field[$i])) {
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_reward_points', stripslashes($_text_field[$i]));                   
        }
        
        $point_select = $_POST['_enable_reward_points_price'];
        
        if (isset($point_select[$i])) {
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points_price', stripslashes($point_select[$i]));
        }
        
        $point_text = $_POST['price_points'];
        if (isset($point_text[$i])) {
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, 'price_points', stripslashes($point_text[$i]));
        }
        
        $percent_text_field = $_POST['_reward_percent'];
        if (isset($percent_text_field[$i])) {                    
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_reward_percent', stripslashes($percent_text_field[$i]));
        }
        
        //select
        $new_select = $_POST['_select_reward_rule'];
        if (isset($new_select[$i])) {                    
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_select_reward_rule', stripslashes($new_select[$i]));
        }
        
        $_text_fields = $_POST['_referral_reward_points'];
        if (isset($_text_field[$i])) {                    
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_referral_reward_points', stripslashes($_text_fields[$i]));
        }
        
        $percent_text_fields = $_POST['_referral_reward_percent'];
        if (isset($percent_text_field[$i])) {                    
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_referral_reward_percent', stripslashes($percent_text_fields[$i]));
        }
        
        //select
        $new_selects = $_POST['_select_referral_reward_rule'];
        if (isset($new_select[$i])) {                    
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_select_referral_reward_rule', stripslashes($new_selects[$i]));
        }
        
        // Select
        $_select = $_POST['_enable_reward_points'];
         if (isset($_select[$i])) {
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points', stripslashes($_select[$i]));
            RSFunctionForSavingMetaValues::rewardsystem_update_post_meta($variation_id, '_enable_reward_points', stripslashes($_select[$i]));
         }
            
    }
    
    public static function rs_validation_for_input_field_in_variable_product() {
        ?>

        <script type="text/javascript">
            jQuery(function () {
                jQuery('body').on('blur', '#_reward_points[type=text],\n\
                                           #_reward_percent[type=text],\n\
                                           #_referral_reward_points[type=text],\n\
                                           #_referral_reward_percent[type=text]', function () {
                    jQuery('.wc_error_tip').fadeOut('100', function () {
                        jQuery(this).remove();
                    });

                    return this;
                });

                jQuery('body').on('keyup change', '#_reward_points[type=text],\n\
                                           #_reward_percent[type=text],\n\
                                           #_referral_reward_points[type=text],\n\
                                           #_referral_reward_percent[type=text]', function () {
                    var value = jQuery(this).val();
                    console.log(woocommerce_admin.i18n_mon_decimal_error);
                    var regex = new RegExp("[^\+1-9\%.\\" + woocommerce_admin.mon_decimal_point + "]+", "gi");
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
    
    public static function display_msg_for_variable_product() {
        global $post;
        if (get_option('rs_show_hide_message_for_variable_product') == '1') {
            ?>
            <div id='value_variable_product'></div>
            <?php
        }
    }

    public static function add_variation_shortcode_div() {
        return "<span class='variationrewardpoints' style='display:inline-block'></span>";
    }
    
    public static function display_purchase_msg_for_variable_product() {
        if (is_product()|| is_page()) {
            ?>
            <style type="text/css">
                .variableshopmessage {
                    display:none;
                }
            </style>
            <script type='text/javascript'>
                jQuery(document).ready(function () {
                    jQuery('#value_variable_product').hide();            
                    jQuery(document).on('change', 'select', function () {
                        var variationid = jQuery('input:hidden[name=variation_id]').val();                           
                        if (variationid === '') {
                            return false;
                        }                        
                        var dataparam = ({
                            action: 'getvariationid',
                            variationproductid: variationid,
                            userid: "<?php echo get_current_user_id(); ?>",
                        });                        
                        jQuery.post("<?php echo admin_url('admin-ajax.php');?>", dataparam,function(response) {                              
                                    if (response !== '') {
            <?php
            $banned_user_list = get_option('rs_banned-users_list');
            if (is_user_logged_in()) {
                $userid = get_current_user_id();
                $banning_type = FPRewardSystem::check_banning_type($userid);
                if ($banning_type != 'earningonly' && $banning_type != 'both') {
                    ?>
                                                var splitresponse = response.split('_');                                                
                                                if (splitresponse[0] > 0) {                                                    
                                                    jQuery('.variableshopmessage').show();
                                                    jQuery('#value_variable_product').addClass('woocommerce-info');
                                                    jQuery('#value_variable_product').show();
                                                    jQuery('#value_variable_product').html("<?php echo do_shortcode(get_option('rs_message_for_variation_products')); ?>");
                                                    jQuery('.variationrewardpoints').html(splitresponse[0]);
                                                    jQuery('.variationrewardpointsamount').html(splitresponse[1]);
                                                } else {
                                                    jQuery('#value_variable_product').hide();
                                                    jQuery('.variableshopmessage').hide();
                                                }
                    <?php
                }
            } else {
                ?>
                                            var splitresponse = response.split('_');
                                            if (splitresponse[0] > 0) {
                                                jQuery('.variableshopmessage').show();
                                                jQuery('#value_variable_product').addClass('woocommerce-info');
                                                jQuery('#value_variable_product').show();
                                                jQuery('#value_variable_product').html("<?php echo do_shortcode(get_option('rs_message_for_variation_products')); ?>");
                                                jQuery('.variationrewardpoints').html(splitresponse[0]);
                                                jQuery('.variationrewardpointsamount').html(splitresponse[1]);
                                            } else {
                                                jQuery('#value_variable_product').hide();
                                                jQuery('.variableshopmessage').hide();
                                            }
                <?php
            }
            ?>
                                    }
                                });
                    });
                    jQuery(document).on('change', '.wcva_attribute_radio', function (e) {
                        e.preventDefault();
                        var variationid = jQuery('input:hidden[name=variation_id]').val();
                        if (variationid === '') {
                            return false;
                        }                      
                        var dataparam = ({
                            action: 'getvariationid',
                            variationproductid: variationid,
                            userid: "<?php echo get_current_user_id(); ?>",
                        });
                        jQuery.post("<?php echo admin_url('admin-ajax.php');
            ?>", dataparam,
                                function (response) {                             
                                    if (response !== '') {
            <?php
            $banned_user_list = get_option('rs_banned-users_list');
            if (is_user_logged_in()) {
                $userid = get_current_user_id();
                $banning_type = FPRewardSystem::check_banning_type($userid);
                if ($banning_type != 'earningonly' && $banning_type != 'both') {
                    ?>
                                                var splitresponse = response.split('_');
                                                if (splitresponse[0] > 0) {
                                                    jQuery('.variableshopmessage').show();
                                                    jQuery('#value_variable_product').show();
                                                    jQuery('#value_variable_product').html("<?php echo do_shortcode(get_option('rs_message_for_variation_products')); ?>");
                                                    jQuery('.variationrewardpoints').html(splitresponse[0]);
                                                    jQuery('.variationrewardpointsamount').html(splitresponse[1]);
                                                } else {
                                                    jQuery('#value_variable_product').hide();
                                                    jQuery('.variableshopmessage').hide();
                                                }
                    <?php
                }
            } else {
                ?>
                                            var splitresponse = response.split('_');
                                            if (splitresponse[0] > 0) {
                                                jQuery('.variableshopmessage').show();
                                                jQuery('#value_variable_product').show();
                                                jQuery('#value_variable_product').html("<?php echo do_shortcode(get_option('rs_message_for_variation_products')); ?>");
                                                jQuery('.variationrewardpoints').html(splitresponse[0]);
                                                jQuery('.variationrewardpointsamount').html(splitresponse[1]);
                                            } else {
                                                jQuery('#value_variable_product').hide();
                                                jQuery('.variableshopmessage').hide();
                                            }
                <?php
            }
            ?>
                                    }
                                });
                    });
                });</script>
            <?php
        }
    }
    
    public static function add_variation_point_values_shortcode() {
        if (get_option('woocommerce_currency_pos') == 'right' || get_option('woocommerce_currency_pos') == 'right_space') {
            return "<div class='variationrewardpointsamount' style='display:inline-block'></div>" . get_woocommerce_currency_symbol();
        } elseif (get_option('woocommerce_currency_pos') == 'left' || get_option('woocommerce_currency_pos') == 'left_space') {
            return get_woocommerce_currency_symbol() . "<div class='variationrewardpointsamount' style='display:inline-block'></div>";
        }
    }
    
    public static function add_shortcode_for_rewardpoints_of_variation() {
        if (isset($_POST['variationproductid'])) {
            $checkenable = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($_POST['variationproductid'], '_enable_reward_points');
            $checkrule = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($_POST['variationproductid'], '_select_reward_rule');
            $getpoints = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($_POST['variationproductid'], '_reward_points');
            $getpercent = RSFunctionForSavingMetaValues::rewardsystem_get_post_meta($_POST['variationproductid'], '_reward_percent');
            $global_enable = get_option('rs_global_enable_disable_sumo_reward');
            $global_reward_type = get_option('rs_global_reward_type');
            $rewardpoints = array('0');
            if ($checkenable == '1') {
                if ($checkrule == '1') {

                    $variable_product1 = new WC_Product_Variation($_POST['variationproductid']);
                    $newparentid = $variable_product1->parent->id;
                    if (get_option('rs_set_price_percentage_reward_points') == '1') {
                        $variationregularprice = $variable_product1->regular_price;
                    } else {
                        $variationregularprice = $variable_product1->price;
                    }
                    if ($getpoints == '') {
                        $term = get_the_terms($newparentid, 'product_cat');
                        if (is_array($term)) {
                            $rewardpoints = array('0');
                            foreach ($term as $term) {
                                $enablevalue = get_woocommerce_term_meta($term->term_id, 'enable_reward_system_category', true);
                                $display_type = get_woocommerce_term_meta($term->term_id, 'enable_rs_rule', true);
                                if (($enablevalue == 'yes') && ($enablevalue != '')) {
                                    if ($display_type == '1') {
                                        if (get_woocommerce_term_meta($term->term_id, 'rs_category_points', true) == '') {
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

                        $getpoints = max($rewardpoints);
                    }
                    if ($_POST['userid'] > 0) {
                        $getpoints = RSMemberFunction::user_role_based_reward_points($_POST['userid'], $getpoints);
                    } else {
                        $getpoints = $getpoints;
                    }
                    $redeemingrspoints = $getpoints / get_option('rs_redeem_point');
                    $updatedredeemingpoints = $redeemingrspoints * get_option('rs_redeem_point_value');
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    echo round($getpoints, $roundofftype) . '_' . round($updatedredeemingpoints, $roundofftype);
                } else {
                    $getpercent = $getpercent / 100;
                    $variable_product1 = new WC_Product_Variation($_POST['variationproductid']);
                    if (get_option('rs_set_price_percentage_reward_points') == '1') {
                        $variationregularprice = $variable_product1->regular_price;
                    } else {
                        $variationregularprice = $variable_product1->price;
                    }
                    $getpercent = $getpercent * $variationregularprice;
                    $pointconversion = get_option('rs_earn_point');
                    $pointconversionvalue = get_option('rs_earn_point_value');
                    $pointswithvalue = $getpercent * $pointconversion;

                    $rsoutput = $pointswithvalue / $pointconversionvalue;
                    if ($getpercent == '') {
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
                        $rsoutput = max($rewardpoints);
                    }

                    if ($_POST['userid'] > 0) {
                        $rsoutput = RSMemberFunction::user_role_based_reward_points($_POST['userid'], $rsoutput);
                    } else {
                        $rsoutput = $rsoutput;
                    }
                    $redeemingrspoints = $rsoutput / get_option('rs_redeem_point');
                    $updatedredeemingpoints = $redeemingrspoints * get_option('rs_redeem_point_value');
                    $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
                    echo round($rsoutput, $roundofftype) . '_' . round($updatedredeemingpoints, $roundofftype);
                }
            } else {
                echo "0_0";
            }
        }
        exit();
    }
}

new RSVariableProduct();