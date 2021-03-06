<?php

class RSMemberLevel {
    
    public function __construct() {   
        
        add_action('init', array($this, 'reward_system_default_settings'),999);// call the init function to update the default settings on page load
        
        add_filter('woocommerce_rs_settings_tabs_array',array($this,'reward_system_tab_setting'));// Register a New Tab in a WooCommerce Reward System Settings        
        
        add_action('woocommerce_rs_settings_tabs_rewardsystem_member_level', array($this, 'reward_system_register_admin_settings'));// Call to register the admin settings in the Reward System Submenu with general Settings tab        
        
        add_action('woocommerce_update_options_rewardsystem_member_level', array($this, 'reward_system_update_settings'));// call the woocommerce_update_options_{slugname} to update the reward system                               
        
    }
    
    /*
     * Function to Define Name of the Tab
     */
    public static function reward_system_tab_setting($setting_tabs){
        $setting_tabs['rewardsystem_member_level'] = __('Member Level','rewardsystem');
        return $setting_tabs;
    }
    
    /*
     * Function label settings to Member Level Tab
     */
    public static function reward_system_admin_fields() {
         global $woocommerce;
         return apply_filters('woocommerce_rewardsystem_member_level_settings', array(
            array(
                'name' => __('Member Level', 'rewardsystem'),
                'type' => 'title',                
                'id' => 'rs_member_level_setting',
            ),
            array(
                'name' => __(''),
                'type' => 'title',
                'desc' => '<h3>[rs_my_current_earning_level_name] - Use this Shortcode for displaying the Current Level of the User <br><br></h3>',
                
            ),
            array(
                'name' => __('Priority Level Selection', 'rewardsystem'),
                'desc' => __('If more than one type(level) is enabled then use the highest/lowest percentage', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_choose_priority_level_selection',
                'class' => 'rs_choose_priority_level_selection',
                'std' => '1',
                'type' => 'radio',
                'newids' => 'rs_choose_priority_level_selection',
                'options' => array(
                    '1' => __('Use the Level that gives Highest Percentage', 'rewardsystem'),
                    '2' => __('Use the Level that gives Lowest Percentage', 'rewardsystem'),
                ),
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => 'rs_member_level_setting', 'class' => 'rs_member_level_setting'),
            array(
                'name' => __('Reward Points Earning Percentage By User Roles', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_user_role_reward_points',
            ),
            array(
                'name' => __('Member Level', 'rewardsystem'),
                'desc' => __('Choose User Role Based Reward Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_enable_user_role_based_reward_points',
                'css' => 'min-width:150px;',
                'std' => 'yes',
                'type' => 'checkbox',
                'newids' => 'rs_enable_user_role_based_reward_points',
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_user_role_reward_points'),
            array(
                'name' => __('Reward Points Earning Percentage By Total Earned Points', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_member_level_earning_points',
            ),
            array(
                'name' => __('Earning Level', 'rewardsystem'),
                'desc' => __('Choose Earned Level based Total Earned Points', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_enable_earned_level_based_reward_points',
                'css' => 'min-width:150px;',
                'std' => 'no',
                'type' => 'checkbox',
                'newids' => 'rs_enable_earned_level_based_reward_points',
                'desc_tip' => true,
            ),
            array(
                'type' => 'rs_user_role_dynamics',
            ),
            array('type' => 'sectionend', 'id' => '_rs_member_level_earning_points'),
            array(
                'name' => __('Member Level Message Settings', 'rewardsystem'),
                'type' => 'title',
                'id' => '_rs_member_level_message_settings',
            ),
            array(
                'name' => __('Message Displayed for Free Productss', 'rewardsystem'),
                'desc' => __('Enter the Message which will be displayed for the Free Products in Cart', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_free_product_message_info',
                'css' => 'min-width:550px;',
                'std' => 'You have got this product for Reaching [current_level_points] Points',
                'type' => 'textarea',
                'newids' => 'rs_free_product_message_info',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Caption for Free Product', 'rewardsystem'),
                'desc' => __('Enter the Caption which will be displayed when after Free Product is removed from cart', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_free_product_msg_caption',
                'css' => 'min-width:550px;',
                'std' => 'Free Product',
                'type' => 'textarea',
                'newids' => 'rs_free_product_msg_caption',
                'desc_tip' => true,
            ),
            array(
                'name' => __('Display Free Product Message in Cart and Order', 'rewardsystem'),
                'desc' => __('Enable displaying free product message in cart/order', 'rewardsystem'),
                'tip' => '',
                'id' => 'rs_remove_msg_from_cart_order',
                'css' => 'min-width:150px;',
                'std' => 'yes',
                'type' => 'checkbox',
                'newids' => 'rs_remove_msg_from_cart_order',
                'desc_tip' => true,
            ),
            array('type' => 'sectionend', 'id' => '_rs_member_level_message_settings'),
         ));
     }
     
    /**
     * Registering Custom Field Admin Settings of Sumo Reward Points in woocommerce admin fields funtion
     */
    public static function reward_system_register_admin_settings() {
        
        woocommerce_admin_fields(RSMemberLevel::reward_system_admin_fields());
    }

    /**
     * Update the Settings on Save Changes may happen in Sumo Reward Points
     */
    public static function reward_system_update_settings() {
        woocommerce_update_options(RSMemberLevel::reward_system_admin_fields());
    }

    /**
     * Initialize the Default Settings by looping this function
     */
    public static function reward_system_default_settings() {
        global $woocommerce;
        foreach (RSMemberLevel::reward_system_admin_fields() as $setting)
            if (isset($setting['newids']) && isset($setting['std'])) {
                add_option($setting['newids'], $setting['std']);
            }
    }
}

new RSMemberLevel();