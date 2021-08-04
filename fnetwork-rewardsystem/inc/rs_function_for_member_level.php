<?php

class RSMemberFunction {

    public function __construct() {

        add_action('woocommerce_admin_field_rs_user_role_dynamics', array($this, 'reward_system_add_table_to_action'));

        add_filter("woocommerce_rewardsystem_member_level_settings", array($this, 'reward_system_add_settings_to_action'));

        add_action('woocommerce_update_options_rewardsystem_member_level', array($this, 'save_data_for_earning_level'));
        
        add_action('admin_head',array($this,'rs_validation_of_input_field_in_member_level')); 
        
        //For older version of Woocommerce (i.e) Version < 2.3.0;
        add_action('woocommerce_before_cart_item_quantity_zero', array($this,'fp_remove_cart_item_key'), 10, 1);
        
        //For newer version of Woocommerce (i.e) Version > 2.3.0;
        add_action('woocommerce_cart_item_removed', array($this,'fp_remove_cart_item_key'),1,2); 
        
        add_shortcode('rs_my_current_earning_level_name',array($this,'add_shortcode_for_current_level_name'));
                
    }

    /*
     * Function to add table for Earning Level in Member Level Tab
     */

    public static function reward_system_add_table_to_action() {
        global $woocommerce;
        wp_nonce_field(plugin_basename(__FILE__), 'rsdynamicrulecreation');
        ?>
        <style type="text/css">
            .rs_add_free_product_user_levels{
                width:100%;
            }
            .chosen-container-active{
                position: absolute;
            }
        </style>        
        <?php
        echo RSJQueryFunction::rs_common_ajax_function_to_select_products('rs_add_free_product_user_levels');
        ?>
        <table class="widefat fixed rsdynamicrulecreation" cellspacing="0">
            <thead>
                <tr>

                    <th class="manage-column column-columnname" scope="col"><?php _e('Name', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Total Earned Points', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Reward Percentage', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Free Products', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname num" scope="col"><?php _e('Remove Level', 'rewardsystem'); ?></th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="manage-column column-columnname num" scope="col"> <span class="add button-primary"><?php _e('Add Level', 'rewardsystem'); ?></span></td>
                </tr>
                <tr>

                    <th class="manage-column column-columnname" scope="col"><?php _e('Name', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Total Earned Points', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Reward Percentage', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname" scope="col"><?php _e('Free Products', 'rewardsystem'); ?></th>
                    <th class="manage-column column-columnname num" scope="col"><?php _e('Add Level', 'rewardsystem'); ?></th>

                </tr>
            </tfoot>

            <tbody id="here">
                <?php
                $rewards_dynamic_rulerule = get_option('rewards_dynamic_rule');
                if (!empty($rewards_dynamic_rulerule)) {
                    if (is_array($rewards_dynamic_rulerule)) {
                        foreach ($rewards_dynamic_rulerule as $i => $rewards_dynamic_rule) {
                            ?>
                            <tr>
                                <td class="column-columnname">
                                    <p class="form-field">
                                        <input type="text" name="rewards_dynamic_rule[<?php echo $i; ?>][name]" class="short" value="<?php echo $rewards_dynamic_rule['name']; ?>"/>
                                    </p>
                                </td>
                                <td class="column-columnname">
                                    <p class="form-field">
                                        <input type="number" name="rewards_dynamic_rule[<?php echo $i; ?>][rewardpoints]" id="rewards_dynamic_rewardpoints<?php echo $i; ?>" class="short" value="<?php echo $rewards_dynamic_rule['rewardpoints']; ?>"/>
                                    </p>
                                </td>
                                <td class="column-columnname">
                                    <p class="form-field">
                                        <input type ="number" name="rewards_dynamic_rule[<?php echo $i; ?>][percentage]" id="rewards_dynamic_rule_percentage<?php echo $i; ?>" class="short test" value="<?php echo $rewards_dynamic_rule['percentage']; ?>"/>
                                    </p>
                                </td>
                                <td class="column-columnname">
                                    <p class="form-field">

                                        <?php
                                        if ((float) $woocommerce->version > (float) ('2.2.0')) {
                                            ?>
                                            <!-- For Latest -->                                            
                                            <input type="hidden" class="wc-product-search" style="width: 100%;" id="rewards_dynamic_rule[<?php echo $i;?>][product_list][]" name="rewards_dynamic_rule[<?php echo $i; ?>][product_list][]" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" data-selected="<?php
                                            $json_ids = array();
                                            if ($rewards_dynamic_rule['product_list'] != "") {
                                                $list_of_produts = $rewards_dynamic_rule['product_list']['0'];

                                                $product_ids = array_filter(array_map('absint', (array) explode(',', $list_of_produts)));


                                                foreach ($product_ids as $product_id) {
                                                    $product = wc_get_product($product_id);
                                                    $json_ids[$product_id] = wp_kses_post($product->get_formatted_name());
                                                } echo esc_attr(json_encode($json_ids));
                                            }
                                            ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" />
                                                   <?php
                                               } else {
                                                   ?>
                                            <!-- For Old Version -->
                                            <select multiple name="rewards_dynamic_rule[<?php echo $i; ?>][product_list][]" class="rs_add_free_product_user_levels">
                                                <?php
                                                if ($rewards_dynamic_rule['product_list'] != "") {
                                                    $list_of_produts = $rewards_dynamic_rule['product_list'];
                                                    foreach ($list_of_produts as $rs_free_id) {
                                                        echo '<option value="' . $rs_free_id . '" ';
                                                        selected(1, 1);
                                                        echo '>' . ' #' . $rs_free_id . ' &ndash; ' . get_the_title($rs_free_id);
                                                        ?>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <option value=""></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                            <?php
                                        }
                                        ?>



                                    </p>
                                </td>
                                <td class="column-columnname num">
                                    <span class="remove button-secondary"><?php _e('Remove Level', 'rewardsystem'); ?></span>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                }
                ?>
            </tbody>
        </table>
        <script type="text/javascript">
            jQuery(document).ready(function () {                
                jQuery(".add").on('click', function () {
                    jQuery('#afterclick').show();
                    var countrewards_dynamic_rule = Math.round(new Date().getTime() + (Math.random() * 100));

        <?php if ((float) $woocommerce->version > (float) ('2.2.0')) { ?>

                        jQuery('#here').append('<tr><td><p class="form-field"><input type="text" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][name]" class="short" value=""/></p></td>\n\
                                                   \n\<td><p class="form-field"><input type="number" id="rewards_dynamic_ruleamount' + countrewards_dynamic_rule + '" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][rewardpoints]" class="short" value=""/></p></td>\n\
                                          \n\\n\
                                        <td><p class="form-field"><input type ="number" id="rewards_dynamic_rule_claimcount' + countrewards_dynamic_rule + '" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][percentage]" class="short test"  value=""/></p></td>\n\\n\
                                        \n\<td><p class="form-field">\n\
                                        \n\
                                        <input type=hidden style="width:100%;" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][product_list][]" class="wc-product-search" data-placeholder="Search for a product" data-action="woocommerce_json_search_products_and_variations" data-multiple="true"/></p></td>n\
                                        <td class="num"><span class="remove button-secondary">Remove Rule</span></td></tr><hr>');

                        jQuery('body').trigger('wc-enhanced-select-init');
        <?php } else { ?>
                        jQuery('#here').append('<tr><td><p class="form-field"><input type="text" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][name]" class="short" value=""/></p></td>\n\
                                        \n\<td><p class="form-field"><input type="number" id="rewards_dynamic_ruleamount' + countrewards_dynamic_rule + '" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][rewardpoints]" class="short" value=""/></p></td>\n\
                                        \n\\n\
                                        <td><p class="form-field"><input type ="number" id="rewards_dynamic_rule_claimcount' + countrewards_dynamic_rule + '" name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][percentage]" class="short test"  value=""/></p></td>\n\\n\
                                        \n\<td><p class="form-field">\n\
                                        \n\
                                        <select multiple name="rewards_dynamic_rule[' + countrewards_dynamic_rule + '][product_list][]" class="rs_add_free_product_user_levels"><option value=""></option></select></p></td>n\
                                        <td class="num"><span class="remove button-secondary">Remove Rule</span></td></tr><hr>');

        <?php } ?>

        <?php if ((float) $woocommerce->version <= (float) ('2.2.0')) { ?>
                        jQuery(function () {
                            jQuery("select.rs_add_free_product_user_levels").ajaxChosen({
                                method: 'GET',
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                dataType: 'json',
                                afterTypeDelay: 100,
                                data: {
                                    action: 'woocommerce_json_search_products_and_variations',
                                    security: '<?php echo wp_create_nonce("search-products"); ?>'
                                }
                            }, function (data) {
                                var terms = {};

                                jQuery.each(data, function (i, val) {
                                    terms[i] = val;
                                });
                                return terms;
                            });
                        });
        <?php } ?>
                    return false;
                });
                jQuery(document).on('click', '.remove', function () {
                    jQuery(this).parent().parent().remove();
                });


                jQuery('#rs_enable_user_role_based_reward_points').addClass('rs_enable_user_role_based_reward_points');
                jQuery('#rs_enable_earned_level_based_reward_points').addClass('rs_enable_user_role_based_reward_points');


                if (jQuery('#rs_enable_user_role_based_reward_points').is(':checked')) {
                    jQuery('.rewardpoints_userrole').parent().parent().show();
                } else {
                    jQuery('.rewardpoints_userrole').parent().parent().hide();
                }


                if (jQuery('#rs_enable_earned_level_based_reward_points').is(':checked')) {
                    jQuery('.rsdynamicrulecreation').show();
                } else {
                    jQuery('.rsdynamicrulecreation').hide();
                }
                jQuery(document).on('click', '#rs_enable_user_role_based_reward_points', function () {
                    if (jQuery(this).is(":checked")) {
                        jQuery('.rewardpoints_userrole').parent().parent().show();
                    } else {
                        jQuery('.rewardpoints_userrole').parent().parent().hide();
                    }

                });

                jQuery(document).on('click', '#rs_enable_earned_level_based_reward_points', function () {
                    if (jQuery(this).is(":checked")) {
                        jQuery('.rsdynamicrulecreation').show();
                    } else {
                        jQuery('.rsdynamicrulecreation').hide();
                    }
                });
            });
        </script>

        <?php
    }

    /*
     * Function to add settings for Member Level in Member Level Tab
     */

    public static function reward_system_add_settings_to_action($settings) {
        global $wp_roles;
        $updated_settings = array();
        $mainvariable = array();
        global $woocommerce;
        foreach ($settings as $section) {
            if (isset($section['id']) && '_rs_user_role_reward_points' == $section['id'] &&
                    isset($section['type']) && 'sectionend' == $section['type']) {

                foreach ($wp_roles->role_names as $value => $key) {
                    $updated_settings[] = array(
                        'name' => __('Reward Points for ' . $value . ' User Role in Percentage %', 'rewardsystem'),
                        'desc' => __('Please Enter Percentage of Reward Points for ' . $value, 'rewardsystem'),
                        'tip' => '',
                        'class' => 'rewardpoints_userrole',
                        'id' => 'rs_reward_user_role_' . $value,
                        'css' => 'min-width:150px;',
                        'std' => '100',
                        'type' => 'text',
                        'newids' => 'rs_reward_user_role_' . $value,
                        'desc_tip' => true,
                    );
                }

                $updated_settings[] = array(
                    'type' => 'sectionend', 'id' => '_rs_reward_system_payment_gateway',
                );
            }


            $updated_settings[] = $section;
        }

        return $updated_settings;
    }

    /*
     * Function to save data for earning level.
     */

    public static function save_data_for_earning_level() {

        $getpostvalue = $_POST['rewards_dynamic_rule'];

        update_option('rewards_dynamic_rule', $getpostvalue);
    }
    
    public static function rs_validation_of_input_field_in_member_level(){        
        ?>

        <script type="text/javascript">
            jQuery(function () {
                jQuery('body').on('blur', '#rs_reward_user_role_administrator[type=text],\n\
                                           #rs_reward_user_role_editor[type=text],\n\
                                           #rs_reward_user_role_author[type=text],\n\
                                           #rs_reward_user_role_contributor[type=text],\n\
                                           #rs_reward_user_role_subscriber[type=text],\n\
                                           #rs_reward_user_role_customer[type=text],\n\
                                           #rs_reward_user_role_shop_manager[type=text]', function () {
                    jQuery('.wc_error_tip').fadeOut('100', function () {
                        jQuery(this).remove();
                    });

                    return this;
                });

                jQuery('body').on('keyup change', '#rs_reward_user_role_administrator[type=text],\n\
                                           #rs_reward_user_role_editor[type=text],\n\
                                           #rs_reward_user_role_author[type=text],\n\
                                           #rs_reward_user_role_contributor[type=text],\n\
                                           #rs_reward_user_role_subscriber[type=text],\n\
                                           #rs_reward_user_role_customer[type=text],\n\
                                           #rs_reward_user_role_shop_manager[type=text]', function () {
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
    
    public static function user_role_based_reward_points($getuserid, $userpoints) {

        //Set Bool Value for User ID
        $userrole = get_option('rs_enable_user_role_based_reward_points');
        $earnuserrole = get_option('rs_enable_earned_level_based_reward_points');

        if (($userrole == 'yes') && ($earnuserrole != 'yes')) {            
            $user = new WP_User($getuserid);
            $user_roles = $user->roles;
            $currentuserrole = $user_roles[0];
            $getcurrentrolepercentage = get_option('rs_reward_user_role_' . $currentuserrole) != '' ? get_option('rs_reward_user_role_' . $currentuserrole) : '100';
            $currentpoints = $userpoints;
            $calculation = $currentpoints * $getcurrentrolepercentage;
            $calculation = $calculation / 100;
            return $calculation;
        }
        if (($earnuserrole == 'yes') && ($userrole != 'yes')) {
           
            $arrayvalue = self::multi_dimensional_sort(get_option('rewards_dynamic_rule'), 'rewardpoints');
            $rs_total_earned_points_user = RSPointExpiry::get_sum_of_total_earned_points($getuserid);           
            $getpercentage = self::rs_get_percentage_in_dynamic_rule($arrayvalue, 'rewardpoints', $rs_total_earned_points_user);
            $getpercentage = $getpercentage != false ? $getpercentage : '100';
            $calculation = $userpoints * $getpercentage;
            $calculation = $calculation / 100;
            return $calculation;
        }

        if (($userrole == 'yes') && ($earnuserrole == 'yes')) {
            $user = new WP_User($getuserid);
            $user_roles = $user->roles;
            $currentuserrole = $user_roles[0];
            $getcurrentrolepercentage = get_option('rs_reward_user_role_' . $currentuserrole) != '' ? get_option('rs_reward_user_role_' . $currentuserrole) : '100';

            $arrayvalue = self::multi_dimensional_sort(get_option('rewards_dynamic_rule'), 'rewardpoints');
            $rs_total_earned_points_user = RSPointExpiry::get_sum_of_total_earned_points($getuserid);            
            $getpercentage = self::rs_get_percentage_in_dynamic_rule($arrayvalue, 'rewardpoints', $rs_total_earned_points_user);
            $getpercentage = $getpercentage != false ? $getpercentage : '100';

            if (get_option('rs_choose_priority_level_selection') == '1') {
                if ($getcurrentrolepercentage >= $getpercentage) {
                    $getcurrentrolepercentage = $getcurrentrolepercentage;
                } else {
                    $getcurrentrolepercentage = $getpercentage;
                }
            } else {
                if (get_option('rs_choose_priority_level_selection') == '2') {
                    if ($getcurrentrolepercentage <= $getpercentage) {
                        $getcurrentrolepercentage = $getcurrentrolepercentage;
                    } else {
                        $getcurrentrolepercentage = $getpercentage;
                    }
                }
            }

            $currentpoints = $userpoints;
            $calculation = $currentpoints * $getcurrentrolepercentage;
            $calculation = $calculation / 100;
            return $calculation;
        }

        if (($userrole != 'yes') && ($earnuserrole != 'yes')) {
            $getcurrentrolepercentage = 100;
            $currentpoints = $userpoints;
            $calculation = $currentpoints * $getcurrentrolepercentage;
            $calculation = $calculation / 100;
            return $calculation;
        }
    }
    

    public static function multi_dimensional_sort($arr, $index) {
        $b = array();
        $c = array();
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                $b[$key] = $value[$index];
            }

            asort($b);

            foreach ($b as $key => $value) {
                $c[$key] = $arr[$key];
            }


            return $c;
        }
    }
    
    public static function rs_get_percentage_in_dynamic_rule($products, $field, $value) {
        if (is_array($products)) {
            foreach ($products as $key => $product) {
                if ($product[$field] >= $value)
                    return $product['percentage'];
            }
        }else {
            return '100';
        }
        return false;
    }   
    
    public static function delete_saved_product_key_callback() {
        global $wpdb;
        if (isset($_POST['key_to_remove']) && $_POST['current_user_id']) {
            $selected_key_to_delete = $_POST['key_to_remove'];
            $user_id_to_remove = $_POST['current_user_id'];            

            $after_unset = self::unset_saved_keys($selected_key_to_delete, array_filter(array_unique(get_user_meta($user_id_to_remove, 'listsetofids', true))));            
            update_user_meta($user_id_to_remove, 'listsetofids', array_unique($after_unset));


            echo "1";
        }
        exit();
    }
    
    public static function unset_saved_keys($del_val, $messages) {
        if (($key = array_search($del_val, $messages)) !== false) {
            unset($messages[$key]);
        }
        return $messages;
    }        

    public static function fp_remove_cart_item_key($cart_item_key,$object) {    
        $olddataifany = (array) get_user_meta(get_current_user_id(), 'listsetofids', true);
        $arraymergedata = array_unique(array_filter(array_merge($olddataifany, (array) $cart_item_key)));       
        update_user_meta(get_current_user_id(), 'listsetofids', $arraymergedata);
    }
    
    public static function add_shortcode_for_current_level_name(){
       if (is_user_logged_in()) {
                global $woocommerce;
                $userid = get_current_user_id();
                $total_earned_points = RSPointExpiry::get_sum_of_total_earned_points($userid);
                $current_level_id = FPRewardSystem_Free_Product::fp_get_free_product_level_id($total_earned_points);
                $member_level_list = get_option('rewards_dynamic_rule');                   
                $current_level_name = isset($member_level_list[$current_level_id]['name'])?$member_level_list[$current_level_id]['name']:"";                               
                return $current_level_name;                
        }
    }

}

new RSMemberFunction();
