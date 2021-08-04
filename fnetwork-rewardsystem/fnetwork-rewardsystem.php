<?php
/*
 * Plugin Name: FNETWORK -> SUMO Reward Points - kingtheme.net
 * Plugin URI:
 * Description: For internal use
 * Version: 0.1
 * Author: Francisco Matelli Matulovic
 * Author URI: www.franciscomat.com
 */

class FPRewardSystem {
    /*
     * To Avoid Database Error
     */

    public static $dbversion = 1.2;

    /*
     * Initialize the Construct of Sumo Reward Points
     */

    public function __construct() {
        /* Include once will help to avoid fatal error by load the files when you call init hook */
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


        /* Hook to Initiate whether WooCommerce is Active */
       # add_action('init', array($this, 'rs_check_if_woocommerce_is_active'),1);

        /* To Avoid Header Already Sent Problem upon Activation or something */
        add_action('init', array($this, 'prevent_header_already_sent_problem'));

        register_activation_hook(__FILE__, array($this, 'create_table_for_point_expiry'));

        register_activation_hook(__FILE__, array($this, 'create_table_to_record_earned_points_and_redeem_points'));

        register_activation_hook(__FILE__, array($this, 'create_table_for_email_temlpate_in_db'));

        register_activation_hook(__FILE__, array($this, 'encash_reward_points_submitted_data'));
        
        register_activation_hook(__FILE__, array($this, 'send_point_submitted_data'));


        if(is_plugin_active('woocommerce/woocommerce.php')){
            
            add_action('init', array($this, 'rs_included_files'),999);

            /* Reset Reward System Admin Settings */        
            add_action('init', array($this, 'reset_reward_system_admin_settings'),9999);
            
            add_action('init', array($this, 'default_value_for_earning_and_redeem_points'));
        }
        

        /* Load WooCommerce Enqueue Script to Load the Script and Styles by filtering the WooCommerce Screen IDS */
        if (isset($_GET['page'])) {
            if (($_GET['page'] == 'rewardsystem_callback')) {
                add_filter('woocommerce_screen_ids', array($this, 'reward_system_load_default_enqueues'), 9, 1);
            }
        }

        include_once 'inc/class_reward_system_menus.php';

        add_action('wp_enqueue_scripts', array($this, 'rewardsystem_enqueue_script'));

        add_action('admin_enqueue_scripts', array($this, 'rewardsystem_enqueue_script'));              

        add_action('admin_head', array($this, 'import_user_points_to_reward_system'));
        
        add_action('wp_ajax_get_user_list_of_ids', array($this, 'perform_ajax_scenario_getting_list_of_user_ids'));
       
        add_action('wp_ajax_user_points_split_option', array($this, 'perform_ajax_splitted_ids_for_user_ids'));                
        
        add_action('plugins_loaded', array($this, 'rs_translate_file'));
    }

    public static function rs_included_files() {

        /*
         * Including Setting file for all tab.
         */
        include_once 'inc/admin/class_general_tab_setting.php';

        include_once 'inc/admin/class_reward_points_for_action.php';

        include_once 'inc/admin/class_member_level_tab.php';

        include_once 'inc/admin/class_user_reward_points_tab.php';

        include_once 'inc/admin/class_add_remove_points_tab.php';

        include_once 'inc/admin/class_message_tab.php';

        include_once 'inc/admin/class_rs_shop_page_customization.php';

        include_once 'inc/admin/class_rs_single_product_page.php';

        include_once 'inc/admin/class_cart_tab.php';

        include_once 'inc/admin/class_checkout_tab.php';

        include_once 'inc/admin/class_myaccount_tab.php';

        include_once 'inc/admin/class_masterlog_tab.php';

        include_once 'inc/admin/class_referral_reward_tab.php';

        include_once 'inc/admin/class_update_tab.php';

        include_once 'inc/admin/class_status_tab.php';

        include_once 'inc/admin/class_refer_a_friend_tab.php';

        include_once 'inc/admin/class_social_rewards_tab.php';

        include_once 'inc/admin/class_email_template_tab.php';

        include_once 'inc/admin/class_mail_tab.php';

        include_once 'inc/admin/class_sms_tab.php';

        include_once 'inc/admin/class_order_tab.php';

        include_once 'inc/admin/class_gift_voucher_tab.php';

        include_once 'inc/admin/class_import_export_tab.php';

        include_once 'inc/admin/class_form_for_cash_back_tab.php';

        include_once 'inc/admin/class_request_for_cash_back_tab.php';

        include_once 'inc/admin/class_coupon_reward_points_tab.php';

        include_once 'inc/admin/class_manuall_referral_link_tab.php';

        include_once 'inc/admin/class_reports_in_csv_tab.php';

        include_once 'inc/admin/class_reset_tab.php';

        include_once 'inc/admin/class_troubleshoot_tab.php';

        include_once 'inc/admin/class_localization_tab.php';

        include_once 'inc/admin/class_buying_reward_points.php';
        
        include_once 'inc/admin/class_form_for_send_points_tab.php';
        
        include_once 'inc/admin/class_request_for_send_points_tab.php';
                
        include_once 'inc/admin/wc_class_send_point_wplist.php';
        
        include 'inc/rs_wc_booking_compatabilty.php';

        include 'inc/admin/class_nominee_tab.php';
        
        include 'inc/ajax_main_function.php';


        /*
         * Include file for Point Expiry
         */
        include 'inc/admin/main_functions_for_point_expiry.php';

        /*
         * Include file for Settings in Product Level
         */
        include_once 'inc/admin/class_admin_settings_for_simple_product.php';

        include_once 'inc/admin/class_admin_settings_for_variable_product.php';

        include_once 'inc/admin/class_admin_settings_for_category_field.php';

        /*
         * Include Function for saving meta values
         */
        include 'inc/rs_function_for_saving_meta_values.php';

        /*
         * Include Function for all tabs.
         */
        include('inc/rs_function_for_general_tab.php');

        include('inc/rs_function_for_reward_points_for_action.php');

        include('inc/rs_function_for_member_level.php');

        include('inc/rs_function_for_user_reward_points.php');

        include('inc/rs_function_for_add_remove_tab.php');

        include('inc/rs_function_for_message_tab.php');

        include('inc/rs_function_for_cart_tab.php');

        include('inc/rs_function_for_checkout.php');

        include('inc/rs_function_for_myaccount_tab.php');

        include('inc/rs_function_for_masterlog_tab.php');

        include('inc/rs_function_for_referral_reward_tab.php');

        include ('inc/rs_referral_log_count.php');

        include('inc/rs_function_for_update_tab.php');

        include('inc/rs_function_for_status_tab.php');

        include('inc/rs_function_for_refer_a_friend.php');

        include('inc/rs_function_for_social_reward_tab.php');

        include('inc/rs_function_for_email_template.php');

        include('inc/rs_function_for_mail_tab.php');

        include('inc/rs_function_for_sms_tab.php');

        include('inc/rs_function_for_order_tab.php');

        include('inc/rs_function_for_gift_voucher_tab.php');

        include('inc/rs_function_for_import_export.php');

        include('inc/rs_function_for_form_for_cash_back.php');

        include('inc/rs_function_for_request_for_cash_back.php');

        include('inc/rs_function_for_coupon_reward_point_tab.php');

        include('inc/rs_function_for_manual_referral_link_tab.php');

        include('inc/rs_function_for_reports_in_csv_tab.php');

        include('inc/rs_function_for_reset_tab.php');

        include('inc/rs_free_product_main_function.php');
        
        include('inc/rs_function_for_send_points.php');
        
        include('inc/rs_function_for_request_for_send_points.php');

        include('inc/rs_function_for_nominee.php');

        //Function for Jquery
        include_once 'inc/rs_jquery.php';

        // Include Files for List Table

        include 'inc/admin/class_wp_list_table_for_users.php';

        include 'inc/admin/class_wp_list_table_view_log_user.php';

        include 'inc/admin/class_wp_list_table_referral_table.php';

        include 'inc/admin/class_wp_list_table_view_referral_table.php';

        include 'inc/admin/class_wp_list_table_master_log.php';

        include_once 'inc/wc_class_encashing_wplist.php';

        //Include File to add Registration Points to Referror and Referral
        include 'inc/rs_function_to_add_registration_points.php';

        include 'inc/rs_function_to_apply_coupon.php';

        include_once 'inc/admin/class_wpml_support.php';
    }

    /*
     * Function to check wheather Woocommerce is active or not
     */

    public static function rs_check_if_woocommerce_is_active() {

        if (is_multisite()) {
            if (!is_plugin_active_for_network('woocommerce/woocommerce.php') && (!is_plugin_active('woocommerce/woocommerce.php'))) {
                if (is_admin()) {
                    $variable = "<div class='error'><p> SUMO Reward Points will not work until WooCommerce Plugin is Activated. Please Activate the WooCommerce Plugin. </p></div>";
                    echo $variable;
                }
                return;
            }
        } else {
            if (!is_plugin_active('woocommerce/woocommerce.php')) {
                if (is_admin()) {
                    $variable = "<div class='error'><p> SUMO Reward Points will not work until WooCommerce Plugin is Activated. Please Activate the WooCommerce Plugin. </p></div>";
                    echo $variable;
                }
                return;
            }
        }
    }

    /*  */

    /*
     * Load the Default JAVASCRIPT and CSS
     */

    public static function reward_system_load_default_enqueues() {
        global $my_admin_page;
        $newscreenids = get_current_screen();
        if (isset($_GET['page'])) {
            if (($_GET['page'] == 'rewardsystem_callback')) {
                $array[] = $newscreenids->id;
                return $array;
            } else {
                $array[] = '';
                return $array;
            }
        }
    }

    /*
     * Function to Prevent Header Error that says You have already sent the header.
     */

    public static function prevent_header_already_sent_problem() {
        ob_start();
    }

    // Import User Reward Points from Old Version to Latest Version
    public static function import_user_points_to_reward_system() {
        wp_enqueue_script('jquery');        
            ?>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('.gif_rs_sumo_reward_button').css('display', 'none'); 
                });
                jQuery(document).ready(function () {                   
                    jQuery('#rs_add_old_points').click(function(){                        
                        if(confirm("Are you sure you want to Add the Existing points?")){                             
                            jQuery('.gif_rs_sumo_reward_button').css('display', 'inline-block');
                            var dataparam = ({
                                action: 'get_user_list_of_ids'
                            });
                            function getData(id) {
                                console.log(id);
                                return jQuery.ajax({
                                    type: 'POST',
                                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                    data: ({
                                        action: 'user_points_split_option',
                                        ids: id
                                    }),
                                    success: function (response) {
                                        console.log(response);
                                    },
                                    dataType: 'json',
                                    async: false
                                });
                            }                   
                            jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", dataparam,
                                    function (response) {
                                        console.log(response);
                                        if (response !== 'success') {
                                            var j = 1;
                                            var i, j, temparray, chunk = 10;
                                            for (i = 0, j = response.length; i < j; i += chunk) {
                                                temparray = response.slice(i, i + chunk);
                                                getData(temparray);
                                            }
                                            jQuery.when(getData()).done(function (a1) {
                                                console.log('Ajax Done Successfully');
                                                location.reload();
                                            });
                                        } else {
                                            var newresponse = response.replace(/\s/g, '');
                                            if (newresponse === 'success') {
                                                //jQuery('.submit .button-primary').trigger('click');
                                            }
                                        }
                                    }, 'json');
                            return false;
                        }
                    });
                });

            </script>
            <?php       
    }
    

    //Perform Ajax Scenario for Updating User Points
    public static function perform_ajax_scenario_getting_list_of_user_ids() {
        $args = array(
            'fields' => 'ID',
            'meta_key' => '_my_reward_points',
            'meta_value' => '',
            'meta_compare' => '!='
        );
        $get_users = get_users($args);

        echo json_encode($get_users);

        exit();
    }

    // Perform Splitted User IDs in Reward System Function
    public static function perform_ajax_splitted_ids_for_user_ids() {
        //var_dump('gi');
        if (isset($_POST['ids'])) {
            foreach ($_POST['ids'] as $eachid) {
                self::insert_user_points_in_database($eachid);
            }
        }

        exit();
    }

    // Insert User Points in Database

    public static function insert_user_points_in_database($user_id) {
        global $wpdb;        
            $user_points = get_user_meta($user_id, '_my_reward_points', true);                                    
            $table_name = $wpdb->prefix . "rspointexpiry";
            $currentdate = time();
            $query = $wpdb->get_row("SELECT * FROM $table_name WHERE userid = $user_id and expirydate = 999999999999",ARRAY_A);                        
                if(!empty($query)){                                            
                 $id = $query['id'];
                 $oldearnedpoints = $query['earnedpoints'];
                 $oldearnedpoints = $oldearnedpoints + $user_points;
                 $wpdb->update($table_name, array('earnedpoints' => $oldearnedpoints),array('id'=>$id));                   
            }else{
             $wpdb->insert($table_name, array(
                'earnedpoints' => $user_points,
                'usedpoints' => '',
                'expiredpoints' => '0',
                'userid' => $user_id,
                'earneddate' => $currentdate,
                'expirydate' => '999999999999',
                'checkpoints' => 'OUP',
                'orderid' => '',
                'totalearnedpoints' => '',
                'totalredeempoints' => '',
                'reasonindetail' => ''
            ));   
            }                               
    }

    /* Create the rspointexpiry Table structure to perform few more audits */

    public static function create_table_for_point_expiry() {
        // Create Table for Point Expiry

        global $wpdb;
        $getdbversiondata = get_option("rs_point_expiry") != 'false' ? get_option('rs_point_expiry') : "0";
        $table_name = $wpdb->prefix . 'rspointexpiry';
        if ($getdbversiondata != self::$dbversion) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		earnedpoints FLOAT,
                usedpoints FLOAT,
                expiredpoints FLOAT,
                userid INT(99),
                earneddate VARCHAR(999) NOT NULL,
                expirydate VARCHAR(999) NOT NULL,
                checkpoints VARCHAR(999) NOT NULL,
                orderid INT(99),
                totalearnedpoints INT(99),
                totalredeempoints INT(99),
                reasonindetail VARCHAR(999),
         	UNIQUE KEY id (id)
	) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        add_option('rs_point_expiry', self::$dbversion);
        }
        
    }

    public static function create_table_to_record_earned_points_and_redeem_points() {

        global $wpdb;
        $getdbversiondata = get_option("rs_record_points") != 'false' ? get_option('rs_record_points') : "0";
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        if ($getdbversiondata != self::$dbversion) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		earnedpoints FLOAT,
                redeempoints FLOAT,                
                userid INT(99),
                earneddate VARCHAR(999) NOT NULL,
                expirydate VARCHAR(999) NOT NULL,                
                checkpoints VARCHAR(999) NOT NULL,
                earnedequauivalentamount INT(99),
                redeemequauivalentamount INT(99),
                orderid INT(99),
                productid INT(99),
                variationid INT(99),
                refuserid INT(99),
                reasonindetail VARCHAR(999),
                totalpoints INT(99),
                showmasterlog VARCHAR(999),
                showuserlog VARCHAR(999),
                nomineeid INT(99),
                nomineepoints INT(99),
         	UNIQUE KEY id (id)
	) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        add_option('rs_record_points', self::$dbversion);
        }
    }

    public static function create_table_for_email_temlpate_in_db() {
//Email Template Table
        global $wpdb;
        $table_name_email = $wpdb->prefix . 'rs_templates_email';
        $sql = "CREATE TABLE $table_name_email (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                template_name LONGTEXT NOT NULL,
                sender_opt VARCHAR(10) NOT NULL DEFAULT 'woo',
                from_name LONGTEXT NOT NULL,
                from_email LONGTEXT NOT NULL,
                subject LONGTEXT NOT NULL,
                message LONGTEXT NOT NULL,
                earningpoints LONGTEXT NOT NULL,
                redeemingpoints LONGTEXT NOT NULL,
                mailsendingoptions LONGTEXT NOT NULL,
                rsmailsendingoptions LONGTEXT NOT NULL,
                minimum_userpoints LONGTEXT NOT NULL,
                sendmail_options VARCHAR(10) NOT NULL DEFAULT '1',
                sendmail_to LONGTEXT NOT NULL,
                sending_type VARCHAR(20) NOT NULL,
                UNIQUE KEY id (id)
              )DEFAULT CHARACTER SET utf8;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        $email_temp_check = $wpdb->get_results("SELECT * FROM $table_name_email", OBJECT);
        if (empty($email_temp_check)) {
            $wpdb->insert($table_name_email, array('template_name' => 'Default',
                'sender_opt' => 'woo',
                'from_name' => 'Admin',
                'from_email' => get_option('admin_email'),
                'subject' => 'SUMO Rewards Point',
                'message' => 'Hi {rsfirstname} {rslastname}, <br><br> You have Earned Reward Points: {rspoints} on {rssitelink}  <br><br> You can use this Reward Points to make discounted purchases on {rssitelink} <br><br> Thanks',
                'minimum_userpoints' => '0',
                'mailsendingoptions' => '2',
                'rsmailsendingoptions' => '3',
            ));
        }
    }

    public static function encash_reward_points_submitted_data() {
        global $wpdb;
        $charset_collate = '';
        $table_name = $wpdb->prefix . "sumo_reward_encashing_submitted_data";
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }
        $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  userid INT(225),
  userloginname VARCHAR(200),
  pointstoencash VARCHAR(200),
  pointsconvertedvalue VARCHAR(200),
  encashercurrentpoints VARCHAR(200),
  reasonforencash LONGTEXT,
  encashpaymentmethod VARCHAR(200),
  paypalemailid VARCHAR(200),
  otherpaymentdetails LONGTEXT,
  status VARCHAR(200),
  date VARCHAR(300),
  UNIQUE KEY id (id)
) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
    
    public static function send_point_submitted_data(){
         global $wpdb;
        $charset_collate = '';
        $table_name = $wpdb->prefix . "sumo_reward_send_point_submitted_data";
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }
        $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  userid INT(225),
  userloginname VARCHAR(200),
  pointstosend VARCHAR(200),
  sendercurrentpoints VARCHAR(200),
  status VARCHAR(200),
  selecteduser LONGTEXT NOT NULL,
  date VARCHAR(300),
  UNIQUE KEY id (id)
) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        
    }

    public function rewardgateway() {

        include 'inc/admin/class_rewardgateway.php';

        add_action('plugins_loaded', 'init_reward_gateway_class');
    }

    public static function rewardsystem_enqueue_script() {
        wp_register_script('wp_reward_footable', plugins_url('rewardsystem/js/footable.js'));
        wp_register_script('wp_reward_footable_sort', plugins_url('rewardsystem/js/footable.sort.js'));
        wp_register_script('wp_reward_footable_paging', plugins_url('rewardsystem/js/footable.paginate.js'));
        wp_register_script('wp_reward_footable_filter', plugins_url('rewardsystem/js/footable.filter.js'));
        wp_register_style('wp_reward_footable_css', plugins_url('rewardsystem/css/footable.core.css'));
        wp_register_style('wp_reward_bootstrap_css', plugins_url('rewardsystem/css/bootstrap.css'));
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp_reward_footable');
        wp_enqueue_script('wp_reward_footable_sort');
        wp_enqueue_script('wp_reward_footable_paging');
        wp_enqueue_script('wp_reward_footable_filter');
        wp_enqueue_style('wp_reward_footable_css');
        wp_enqueue_style('wp_reward_bootstrap_css');
    }

    public static function reset_reward_system_admin_settings() {
        if (!empty($_POST['reset'])) {
            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'woocommerce-reset_settings'))
                die(__('Action failed. Please refresh the page and retry.', 'rewardsystem'));

            foreach (RSGeneralTabSetting::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSRewardPointsForAction::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSMessage::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSMyaccount::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSCart::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSCheckout::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSReferAFriend::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSMemberLevel::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSTroubleshoot::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSSocialReward::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSMail::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSStatus::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSLocalization::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSFormForCashBack::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSGiftVoucher::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            foreach (RSCouponRewardPoints::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            delete_option('rewards_dynamic_rule_couponpoints');
            foreach (RSSms::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSUpdate::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            foreach (RSNominee::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }
            
            foreach (RSFormForSendPoints::reward_system_admin_fields() as $setting) {
                if (isset($setting['newids']) && isset($setting['std'])) {
                    delete_option($setting['newids']);
                    add_option($setting['newids'], $setting['std']);
                }
            }

            delete_option('rewards_dynamic_rule_manual');

            delete_transient('woocommerce_cache_excluded_uris');

            $redirect = esc_url_raw(add_query_arg(array('saved' => 'true')));
            if (isset($_POST['reset'])) {
                wp_safe_redirect($redirect);
                exit;
            }
        }
    }
    
    public static function rs_translate_file() {
        load_plugin_textdomain('rewardsystem', false, dirname(plugin_basename(__FILE__)) . '/languages');        
    }
    
    public static function default_value_for_earning_and_redeem_points() {
        add_option('rs_earn_point', '1');
        add_option('rs_earn_point_value', '1');
        add_option('rs_redeem_point', '1');
        add_option('rs_redeem_point_value', '1');
        add_option('rs_redeem_point_for_cash_back', '1');
        add_option('rs_redeem_point_value_for_cash_back', '1');
    }
    
    public static function check_banning_type($userid) {
        $earning = get_option('rs_enable_banning_users_earning_points');
        $redeeming = get_option('rs_enable_banning_users_redeeming_points');


        $banned_user_list = get_option('rs_banned_users_list');
        if (is_array($banned_user_list)) {
            $banned_user_list = $banned_user_list;
        } else {
            $banned_user_list = explode(',', $banned_user_list);
        }

        if (in_array($userid, (array) $banned_user_list)) {
            if ($earning == 'no' && $redeeming == 'no') {
                return "no_banning";
            }

            if ($earning == 'no' && $redeeming == 'yes') {

                return 'redeemingonly';
            }
            if ($earning == 'yes' && $redeeming == 'no') {
                return 'earningonly';
            }
            if ($earning == 'yes' && $redeeming == 'yes') {
                return 'both';
            }
        } else {
            $getarrayofuserdata = get_userdata(get_current_user_id());
            $banninguserrole = get_option('rs_banning_user_role');
            if (in_array(isset($getarrayofuserdata->roles[0]) ? $getarrayofuserdata->roles[0] : '0', (array) $banninguserrole)) {
                if ($earning == 'no' && $redeeming == 'no') {
                    return "no_banning";
                }

                if ($earning == 'no' && $redeeming == 'yes') {

                    return 'redeemingonly';
                }
                if ($earning == 'yes' && $redeeming == 'no') {
                    $banned_user_list = get_option('rs_banned_users_list');
                    return 'earningonly';
                }
                if ($earning == 'yes' && $redeeming == 'yes') {
                    $banned_user_list = get_option('rs_banned_users_list');
                    return 'both';
                }
            }
        }
    }

}

$obj = new FPRewardSystem();

$obj->rewardgateway();
