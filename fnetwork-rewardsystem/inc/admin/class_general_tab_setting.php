<?php

/*
 * General Tab Setting
 */

class RSGeneralTabSetting {

    public function __construct() {
        add_action('init', array($this, 'reward_system_default_settings'), 999); // call the init function to update the default settings on page load

        add_filter('woocommerce_rs_settings_tabs_array', array($this, 'reward_system_tab_setting')); // Register a New Tab in a WooCommerce Reward System Settings

        add_action('woocommerce_rs_settings_tabs_rewardsystem_general', array($this, 'reward_system_register_admin_settings')); // Call to register the admin settings in the Reward System Submenu with general Settings tab

        add_action('woocommerce_update_options_rewardsystem_general', array($this, 'reward_system_update_settings')); // call the woocommerce_update_options_{slugname} to update the reward system
    }

    /*
     * Function to Define Name of the Tab.
     */

    public static function reward_system_tab_setting($setting_tabs) {
        $setting_tabs['rewardsystem_general'] = __('General', 'rewardsystem');
        return $setting_tabs;
    }

    /*
     * Function label settings in General Tab
     */

    public static function reward_system_admin_fields() {
        global $woocommerce;
        global $wp_roles;
        foreach ($wp_roles->roles as $values => $key) {
            $userroleslug[] = $values;
            $userrolename[] = $key['name'];
        }

        $newcombineduserrole = array_combine((array) $userroleslug, (array) $userrolename);
        return apply_filters('woocommerce_rewardsystem_general_settings', array(
            array(
                'name' => __('General Settings', 'rewardsystem'),
                'type' => 'title',
                'desc' => __(''),
                'id' => 'rs_general_tab_setting',
            ),
            array(                
                'type' => 'rs_refresh_button',                
            ),
            array(
                'name' => __('Reward Type - "By Percentage of Product Price" is Calculated based on', 'rewardsystem'),
                'desc' => __('Select Regular/Sale Price for Reward Type - "By Percentage of Product Price" Calculation', 'rewardsystem'),
                'id' => 'rs_set_price_to_calculate_rewardpoints_by_percentage',
                'type' => 'select',
                'css' => 'min-width:150px;',
                'newids' => 'rs_set_price_to_calculate_rewardpoints_by_percentage',
                'std' => '1',
                'options' => array(
                    '1' => __('Regular Price', 'rewardsystem'),
                    '2' => __('Sales Price (Regular Price if Sale Price is unavailable)', 'rewardsystem'),
                ),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Maximum Earning Points for each User', 'rewardsystem'),
                'desc' => __('Enable Maximum Earning Points for each User', 'rewardsystem'),
                'id' => 'rs_enable_disable_max_earning_points_for_user',
                'type' => 'checkbox',
                'std' => 'no',
                'newids' => 'rs_enable_disable_max_earning_points_for_user',
            ),
            array(
                'name' => __('Maximum Earning Points for each User', 'rewardsystem'),
                'desc' => __('Enter a Fixed or Decimal Number greater than 0', 'rewardsystem'),
                'id' => 'rs_max_earning_points_for_user',
                'css' => 'min-width:150px;',
                'std' => '',
                'desc_tip' => true,
                'newids' => 'rs_max_earning_points_for_user',
                'type' => 'text',
            ),
            array('type' => 'sectionend', 'id' => 'rs_general_tab_setting'),
             array(
                'name' => __('Global Settings Point Price', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_global_Point_price'
            ),
             array(
                'name' => __('Enable Point Pricing', 'rewardsystem'),
              
                'id' => 'rs_enable_disable_point_priceing',
                'default' => '1',
                'newids' => 'rs_enable_disable_point_priceing',
                  'type' => 'select',
                'options' => array(
                    '1' => __('Enable', 'rewardsystem'),
                    '2' => __('Disable', 'rewardsystem'),
                ),
            ),
            
              array(
                'name' => __('Label for Point', 'rewardsystem'),
                'desc' => __('Enter label value to display point', 'rewardsystem'),
                'id' => 'rs_label_for_point_value',
                'css' => 'min-width:150px;',
               'default' => '/Pt',
                  'std' => '/Pt',
                'desc_tip' => true,
                'newids' => 'rs_label_for_point_value',
                'type' => 'text',
            ),
              array('type' => 'sectionend', 'id' => '_rs_global_Point_price'),
            array(
                'name' => __('Global Settings for Reward Points', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_global_reward_points'
            ),
            
             array(
                'name' => __('Enable  Points Prices', 'rewardsystem'),                
                'id' => 'rs_local_enable_disable_point_price_for_product',
                'css' => 'min-width:150px;',
                'std' => '2',
                'default' => '2',
                'placeholder' => '',
                'desc_tip' => true,
                 'newids' => 'rs_local_enable_disable_point_price_for_product',
                'type' => 'select',
                'options' => array(
                    '1' => __('Enable', 'rewardsystem'),
                    '2' => __('Disable', 'rewardsystem'),
                ),
            ),
             
             array(
                'name' => __('Pricing Points', 'rewardsystem'),
                'desc' => __('Please Enter Price Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_local_price_points_for_product',
                'class' => 'rs_local_price_points_for_product',
                'css' => 'min-width:150px;',
                'std' => ' ',
                'type' => 'text',
                'newids' => 'rs_local_price_points_for_product',
                'placeholder' => '',                
                'desc_tip' => true,
            ),
            
            array(
                'name' => __('Enable SUMO Reward Points', 'rewardsystem'),
                'id' => 'rs_global_enable_disable_sumo_reward',
                'css' => 'min-width:150px;',
                'std' => '2',
                'default' => '2',
                'placeholder' => '',
                'desc_tip' => true,
                'desc' => __('Global Settings will be considered when Product and Category Settings are Enabled and Values are Empty. '
                        . 'Priority Order is Product Settings, Category Settings and Global Settings in the Same Order. ', 'rewardsystem'),
                'newids' => 'rs_global_enable_disable_sumo_reward',
                'type' => 'select',
                'options' => array(
                    '1' => __('Enable', 'rewardsystem'),
                    '2' => __('Disable', 'rewardsystem'),
                ),
            ),
            array(
                'name' => __('Reward Type', 'rewardsystem'),
                'desc' => __('Select Reward Type by Points/Percentage', 'rewardsystem'),
                'id' => 'rs_global_reward_type',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => '1',
                'default' => '1',
                'desc_tip' => true,
                'newids' => 'rs_global_reward_type',
                'type' => 'select',
                'options' => array(
                    '1' => __('By Fixed Reward Points', 'rewardsystem'),
                    '2' => __('By Percentage of Product Price', 'rewardsystem'),
                ),
            ),
            array(
                'name' => __('Reward Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_global_reward_points',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => ' ',
                'type' => 'text',
                'newids' => 'rs_global_reward_points',
                'placeholder' => '',
                'desc' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                        . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Reward Points in Percent %', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_global_reward_percent',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => ' ',
                'type' => 'text',
                'newids' => 'rs_global_reward_percent',
                'placeholder' => '',
                'desc' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                        . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Referral Reward Type', 'rewardsystem'),
                'desc' => __('Select Reward Type by Points/Percentage', 'rewardsystem'),
                'id' => 'rs_global_referral_reward_type',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => '1',
                'default' => '1',
                'newids' => 'rs_global_referral_reward_type',
                'type' => 'select',
                'desc_tip' => true,
                'options' => array(
                    '1' => __('By Fixed Reward Points', 'rewardsystem'),
                    '2' => __('By Percentage of Product Price', 'rewardsystem'),
                ),
            ),
            array(
                'name' => __('Referral Reward Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_global_referral_reward_point',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => ' ',
                'type' => 'text',
                'newids' => 'rs_global_referral_reward_point',
                'placeholder' => '',
                'desc' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                        . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                'desc_tip' => true,
            ),
            array(
                'name' => __('Referral Reward Points in Percent %', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_global_referral_reward_percent',
                'class' => 'show_if_enable_in_general',
                'css' => 'min-width:150px;',
                'std' => ' ',
                'type' => 'text',
                'newids' => 'rs_global_referral_reward_percent',
                'placeholder' => '',
                'desc' => __('When left empty, Category and Product Settings will be considered in the same order and Current Settings (Global Settings) will be ignored. '
                        . 'When value greater than or equal to 0 is entered then Current Settings (Global Settings) will be considered and Category/Global Settings will be ignored.  ', 'rewardsystem'),
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_global_reward_points'),
            array(
                'name' => __('Earning Points Conversion Settings', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_point_conversion'
            ),
            array(
                'type' => 'earning_conversion',
            ),
            array('type' => 'sectionend', 'id' => '_rs_point_conversion'),
            array(
                'name' => __('Redeeming Points Conversion Settings', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_redeem_point_conversion'
            ),
            array(
                'type' => 'redeeming_conversion',
            ),
            array('type' => 'sectionend', 'id' => '_rs_redeem_point_conversion'),
            array(
                'name' => __('Redeeming Points Conversion Settings for Cash Back', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_redeem_point_conversion_for_cash_back'
            ),
            array(
                'type' => 'redeeming_conversion_for_cash_back',
            ),
            array('type' => 'sectionend', 'id' => '_rs_redeem_point_conversion_cash_back'),
            array(
                'name' => __('Point Expiry Setttings', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_point_setting'
            ),
            array(
                'name' => __('No. of Days in Which Points to be Expired', 'rewardsystem'),
                'type' => 'text',
                'id' => 'rs_point_to_be_expire',
                'class' => 'rs_point_to_be_expire',
                'newids' => 'rs_point_to_be_expire',
                'css' => 'min-width:150px;',
                'std' => '',
                'default' => '',
                'desc' => __('Here you can enter number of days in which  earned reward points to be expired', 'rewardsystem'),
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_point_setting'),
            array(
                'name' => __('Maximum Redeeming Value (Discount) Settings for Order', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_discount_control'
            ),
            array(
                'name' => __('Maximum Redeeming Value (Discount) Type', 'rewardsystem'),
                'desc' => __('', 'rewardsystem'),
                'id' => 'rs_max_redeem_discount',
                'css' => 'min-width:150px;',
                'std' => '',
                'default' => '',
                'newids' => 'rs_max_redeem_discount',
                'type' => 'select',
                'options' => array(
                    '1' => __('By Fixed Value', 'rewardsystem'),
                    '2' => __('By Percentage of Cart Total', 'rewardsystem'),
                ),
                'desc_tip' => false,
            ),
            array(
                'name' => __('Maximum Redeeming Value (Discount) for Order in ' . get_woocommerce_currency_symbol(), 'rewardsystem'),
                'desc' => __('Enter a Fixed or Decimal Number greater than 0', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_fixed_max_redeem_discount',
                'css' => 'min-width:150px;',
                'std' => '',
                'type' => 'text',
                'newids' => 'rs_fixed_max_redeem_discount',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Maximum Discount for Order in Percentage %', 'rewardsystem'),
                'desc' => __('Enter a Fixed or Decimal Number greater than 0', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_percent_max_redeem_discount',
                'css' => 'min-width:150px;',
                'std' => '',
                'type' => 'text',
                'newids' => 'rs_percent_max_redeem_discount',
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_discount_control'),
            array(
                'name' => __('Maximum Cart Total Settings for Sumo Reward Points Gateway', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_discount_control_for_gateway'
            ),            
            array(
                'name' => __('Maximum Cart Total for using Sumo Reward Points Gateway ' , 'rewardsystem'),
                'desc' => __('Enter the Maximum Cart Total that can be used using Sumo Reward Points Gateway', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_max_redeem_discount_for_sumo_reward_points',
                'css' => 'min-width:150px;',
                'std' => '',
                'type' => 'text',
                'newids' => 'rs_max_redeem_discount_for_sumo_reward_points',
                'desc_tip' => true,
            ),            
           array('type' => 'sectionend', 'id' => '_rs_discount_control_for_gateway'),
            array(
                'name' => __('Round Off Settings for Display of Reward Points', 'rewardsystem'),
                'desc' => __('This Settings applies only for Displaying Reward Points and not for Calculation Purpose ', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_round_off_settings',
            ),
            array(
                'name' => __('Round Off Type', 'rewardsystem'),
                'desc' => '',
                'tip' => '',
                'id' => 'rs_round_off_type',
                'css' => 'min-width:150px;',
                'std' => '1',
                'type' => 'select',
                'options' => array(
                    '1' => __('2 Decimal Places', 'rewardsystem'),
                    '2' => __('Whole Number', 'rewardsystem'),
                ),
                'newids' => 'rs_round_off_type',
                'desc_tip' => false,
            ),
            array('type' => 'sectionend', 'id' => '_rs_round_off_settings'),
            array(
                'name' => __('Referral Link Cookies Settings', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_referral_cookies_settings'
            ),
            array(
                'name' => __('Referral Link Cookies Expires in', 'rewardsystem'),
                'desc' => '',
                'id' => 'rs_referral_cookies_expiry',
                'css' => 'min-width:150px;',
                'std' => '3',
                'default' => '3',
                'newids' => 'rs_referral_cookies_expiry',
                'type' => 'select',
                'options' => array(
                    '1' => __('Minutes', 'rewardsystem'),
                    '2' => __('Hours', 'rewardsystem'),
                    '3' => __('Days', 'rewardsystem'),
                ),
                'desc_tip' => false,
            ),
            array(
                'name' => __('Referral Cookies Expiry in Minutes', 'rewardsystem'),
                'desc' => __('Enter a Fixed Number greater than or equal to 0 ', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_referral_cookies_expiry_in_min',
                'css' => 'min-width:150px;',
                'std' => '',
                'type' => 'text',
                'newids' => 'rs_referral_cookies_expiry_in_min',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Referral Cookies Expiry in Hours', 'rewardsystem'),
                'desc' => __('Enter a Fixed Number greater than or equal to 0 ', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_referral_cookies_expiry_in_hours',
                'css' => 'min-width:150px;',
                'std' => '',
                'type' => 'text',
                'newids' => 'rs_referral_cookies_expiry_in_hours',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Referral Cookies Expiry in Days', 'rewardsystem'),
                'desc' => __('Enter a Fixed Number greater than or equal to 0 ', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_referral_cookies_expiry_in_days',
                'css' => 'min-width:150px;',
                'std' => '1',
                'type' => 'text',
                'newids' => 'rs_referral_cookies_expiry_in_days',
                'desc_tip' => true,
            ),
            array( 
                'name' => __('Delete Cookie After X No. of Purchase', 'rewardsystem'), 
                'desc' => __('Enable Delete Cookie After X No. of Purchase', 'rewardsystem'), 
                'id' => 'rs_enable_delete_referral_cookie_after_first_purchase', 
                'css' => 'min-width:150px;', 
                'std' => 'no', 
                'type' => 'checkbox', 
                'newids' => 'rs_enable_delete_referral_cookie_after_first_purchase', 
        ), 
	array( 
                'name' => __('Enter No. of Purchase', 'rewardsystem'), 
                'desc' => __('Enter No. of Purchase in which cookie to deleted', 'rewardsystem'), 
                'tip' => '', 
                'id' => 'rs_no_of_purchase', 
                'css' => 'min-width:150px;', 
                'std' => '', 
                'type' => 'text', 
                'newids' => 'rs_no_of_purchase', 
                'desc_tip' => true, 
         ), 

            array('type' => 'sectionend', 'id' => '_rs_referral_cookies_settings'),
            array(
                'name' => __('Linking Referrals for Life Time', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_life_time_referral',
            ),
            array(
                'name' => __('Linking Referrals for Life Time', 'rewardsystem'),
                'desc' => __('Enable Linking Referrals for Life Time', 'rewardsystem'),
                'id' => 'rs_enable_referral_link_for_life_time',
                'css' => 'min-width:150px;',
                'std' => 'no',
                'type' => 'checkbox',
                'newids' => 'rs_enable_referral_link_for_life_time',
            ),
            array('type' => 'sectionend', 'id' => '_rs_life_time_referral'),
            array(
                'name' => __('Referral Reward Points should be applied to Referrer when User(Buyer or Referral) is X Days Old', 'rewardsystem'),
                'type' => 'title',
                'desc' => __('For eg: If A Refers B then A is the Referrer and B is the Referral', 'rewardsystem'),
                'id' => '_rs_ban_referee_points_time',
            ),
            array(
                'name' => __('Referral Reward Points should be applied to Referrer when User(Buyer or Referral) is X Days Old', 'rewardsystem'),
                'desc' => '',
                'id' => '_rs_select_referral_points_referee_time',
                'css' => 'min-width:150px;',
                'std' => '1',
                'default' => '1',
                'newids' => '_rs_select_referral_points_referee_time',
                'type' => 'select',
                'desc_tip' => false,
                'options' => array(
                    '1' => __('Unlimited', 'rewardsystem'),
                    '2' => __('Limited', 'rewardsystem'),
                ),
            ),
            array(
                'name' => __('Enter Number of Days ', 'rewardsystem'),
                'desc' => __('Enter Fixed Number greater than or equal to 0', 'rewardsystem'),
                'id' => '_rs_select_referral_points_referee_time_content',
                'css' => 'min-width:150px;',
                'newids' => '_rs_select_referral_points_referee_time_content',
                'type' => 'text',
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_ban_referee_points_time'),
            array(
                'name' => __('Gift Icon Settings for Shop, Category and Product Page', 'rewardsystem'),
                'desc' => __('For Variable Products, Shop Page is not Supported', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_gift_icon_selection',
            ),
            array(
                'name' => __('Gift Icon', 'rewardsystem'),
                'desc' => '',
                'id' => '_rs_enable_disable_gift_icon',
                'css' => 'min-width:150px;',
                'std' => '2',
                'default' => '2',
                'newids' => '_rs_enable_disable_gift_icon',
                'type' => 'select',
                'options' => array(
                    '1' => __('Enable', 'rewardsystem'),
                    '2' => __('Disable', 'rewardsystem'),
                ),
                'desc_tip' => false,
            ),
            array(
                'type' => 'uploader',
            ),
            array('type' => 'sectionend', 'id' => '_rs_gift_icon_selection'),
            array(
                'name' => __('Restrict/Ban Users from Using Reward Points', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_ban_users',
            ),
            array(
                'name' => __('Earning Points', 'rewardsystem'),
                'desc' => __('Ban Users from Earning Points', 'rewardsystem'),
                'id' => 'rs_enable_banning_users_earning_points',
                'css' => 'min-width:150px;',
                'std' => '1',
                'type' => 'checkbox',
                'newids' => 'rs_enable_banning_users_earning_points',
            ),
            array(
                'name' => __('Redeeming Points', 'rewardsystem'),
                'desc' => __('Ban Users from Redeeming Points', 'rewardsystem'),
                'id' => 'rs_enable_banning_users_redeeming_points',
                'css' => 'min-width:150px;',
                'std' => 'no',
                'type' => 'checkbox',
                'newids' => 'rs_enable_banning_users_redeeming_points',
            ),
            array(
                'name' => __('Select the Users to Restrict/Ban from Using Reward Points', 'rewardsystem'),
                'desc' => __('Here you select the users whom you wish to ban from using Reward Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_banned_users_list',
                'css' => 'min-width:400px;',
                'std' => '',
                'type' => 'rs_select_user_to_restrict_ban',
                'newids' => 'rs_banned_users_list',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Select the User Roles to Restrict/Ban from Using Reward Points', 'rewardsystem'),
                'desc' => '',
                'tip' => '',
                'id' => 'rs_banning_user_role',
                'css' => 'min-width:343px;',
                'std' => '',
                'placeholder' => 'Search for a User Role',
                'type' => 'multiselect',
                'options' => $newcombineduserrole,
                'newids' => 'rs_banning_user_role',
                'desc_tip' => false,
            ),
            array('type' => 'sectionend', 'id' => '_rs_ban_users'),
        ));
    }

    /**
     * Registering Custom Field Admin Settings of Sumo Reward Points in woocommerce admin fields funtion
     */
    public static function reward_system_register_admin_settings() {

        woocommerce_admin_fields(RSGeneralTabSetting::reward_system_admin_fields());
    }

    /**
     * Update the Settings on Save Changes may happen in Sumo Reward Points
     */
    public static function reward_system_update_settings() {
        woocommerce_update_options(RSGeneralTabSetting::reward_system_admin_fields());
    }

    /**
     * Initialize the Default Settings by looping this function
     */
    public static function reward_system_default_settings() {
        global $woocommerce;
        foreach (RSGeneralTabSetting::reward_system_admin_fields() as $setting)
            if (isset($setting['newids']) && isset($setting['std'])) {
                add_option($setting['newids'], $setting['std']);
            }
    }

}

new RSGeneralTabSetting();
