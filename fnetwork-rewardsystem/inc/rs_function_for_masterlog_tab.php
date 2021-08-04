<?php

class RSFunctionForMasterLog {

    public function __construct() {

        add_action('woocommerce_admin_field_rs_select_users_master_log', array($this, 'rs_select_user_to_export_master_log'));

        add_action('admin_head', array($this, 'rs_add_chosen_to_masterlog_tab'));

        add_action('woocommerce_admin_field_rs_masterlog', array($this, 'rs_list_all_points_log'));

        add_action('wp_ajax_rs_export_masterlog_option', array($this, 'selected_option_masterlog_export_callback'));

        add_action('wp_ajax_rs_list_of_users_masterlog_export', array($this, 'selected_users_for_export_masterlog_callback'));
    }

    public static function rs_select_user_to_export_master_log() {
        global $woocommerce;
        if ((float) $woocommerce->version <= (float) ('2.2.0')) {
            ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_export_masterlog_users_list"><?php _e('Select the users that you wish to Export Master Log', 'rewardsystem'); ?></label>
                </th>
                <td>
                    <select name="rs_export_masterlog_users_list" multiple="multiple" style="width: 350px;" id="rs_export_masterlog_users_list" class="short rs_export_masterlog_users_list">
                        <?php
                        $json_ids = array();
                        $getuser = get_option('rs_export_masterlog_users_list');
                        if ($getuser != "") {
                            $listofuser = $getuser;
                            if (!is_array($listofuser)) {
                                $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                            } else {
                                $userids = $listofuser;
                            }

                            foreach ($userids as $userid) {
                                $user = get_user_by('id', $userid);
                                $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email);
                            }
                            echo esc_attr(json_encode($json_ids));
                        }
                        ?>
                    </select>
                </td>
            </tr>
        <?php } else { ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label for="rs_export_masterlog_users_list"><?php _e('Select the users that you wish to Export Master Log', 'rewardsystem'); ?></label>
                </th>
                <td>
                    <input type="hidden" class="wc-customer-search" name="rs_export_masterlog_users_list" id="rs_export_masterlog_users_list" data-multiple="true" data-placeholder="<?php _e('Search for a customer&hellip;', 'rewardsystem'); ?>" data-selected="<?php
                    $json_ids = array();
                    $getuser = get_option('rs_export_masterlog_users_list');
                    if ($getuser != "") {
                        $listofuser = $getuser;
                        if (!is_array($listofuser)) {
                            $userids = array_filter(array_map('absint', (array) explode(',', $listofuser)));
                        } else {
                            $userids = $listofuser;
                        }

                        foreach ($userids as $userid) {
                            $user = get_user_by('id', $userid);
                            $json_ids[$user->ID] = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' &ndash; ' . esc_html($user->user_email);
                        }echo esc_attr(json_encode($json_ids));
                    }
                    ?>" value="<?php echo implode(',', array_keys($json_ids)); ?>" data-allow_clear="true" />
                </td>
            </tr>
            <?php
        }
    }

    public static function rs_add_chosen_to_masterlog_tab() {
        global $woocommerce;
        global $wpdb;
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        if (isset($_GET['page'])) {
            if ($_GET['page'] == 'rewardsystem_callback') {
                if (isset($_GET['tab'])) {
                    if ($_GET['tab'] == 'rewardsystem_masterlog') {
                        ?>
                        <?php
                        if ((float) $woocommerce->version <= (float) ('2.2.0')) {
                            echo RSJQueryFunction::rs_common_chosen_function('#rs_export_masterlog_users_list');
                        }
                    }
                }
            }
        }
        ?>
        <?php
       
        if (isset($_GET['tab'])) {
                    if ($_GET['tab'] == 'rewardsystem_masterlog') {
                         echo RSJQueryFunction::rs_common_ajax_function_to_select_user('rs_export_masterlog_users_list');
                        ?>
                        <script type="text/javascript">
                            jQuery(document).ready(function () {
                                if ((jQuery('input[name=rs_export_import_masterlog_option]:checked').val()) === '2') {
                                    jQuery('#rs_export_masterlog_users_list').parent().parent().show();
                                } else {
                                    jQuery('#rs_export_masterlog_users_list').parent().parent().hide();
                                }
                                jQuery('input[name=rs_export_import_masterlog_option]:radio').change(function () {
                                    jQuery('#rs_export_masterlog_users_list').parent().parent().toggle();
                                });
                                jQuery(document).ready(function () {
                                    var selected_masterlog_option = jQuery('input[name="rs_export_import_masterlog_option"]').val();
                                    var masterlog_data = {
                                        action: "rs_export_masterlog_option",
                                        export_masterlog_type: selected_masterlog_option,
                                    };
                                    jQuery.post('<?php echo admin_url('admin-ajax.php') ?>', masterlog_data, function (response) {
                                        console.log('Got this from the server: ' + response);
                                    });
                                    jQuery('input[name="rs_export_import_masterlog_option"]').change(function () {
                                        var selected_masterlog_option = jQuery(this).val();
                                        var masterlog_data = {
                                            action: "rs_export_masterlog_option",
                                            export_masterlog_type: selected_masterlog_option,
                                        };
                                        jQuery.post('<?php echo admin_url('admin-ajax.php') ?>', masterlog_data, function (response) {
                                            console.log('Got this from the server: ' + response);
                                        });
                                    });
                                });
                                jQuery(document).ready(function () {
                                    jQuery('#rs_export_masterlog_users_list').change(function () {
                                        var selected_users_mastelog = jQuery(this).val();
                                        var selected_users_masterlog_param = {
                                            action: "rs_list_of_users_masterlog_export",
                                            selected_users_masterlog_export: selected_users_mastelog
                                        };
                                        jQuery.post('<?php echo admin_url('admin-ajax.php') ?>', selected_users_masterlog_param, function (response) {
                                            console.log('Got this from the server: ' + response);
                                        });
                                    });
                                });

                            });
                        </script>

                        <?php
                    }
        }
        $i = 1;
        $masterlog_export_selected_option = get_option('selected_user_type_masterlog');
        $list_users_masterlog_export = get_option('rs_selected_userlist_masterlog_export');
        $getusernickname = '';
        $datas = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        $datas = $datas + (array) get_option('rsoveralllog');

        if (is_array($datas)) {
            if ($masterlog_export_selected_option == '1') {
                foreach ($datas as $values) {
                    if ($i % 2 != 0) {
                        $name = 'alternate';
                    } else {
                        $name = '';
                    }
                    if ($values != '') {
                        if (isset($values['earnedpoints'])) {
                            $orderid = $values['orderid'];
                            $order = new WC_Order($orderid);
                            $checkpoints = $values['checkpoints'];
                            $productid = $values['productid'];
                            $variationid = $values['variationid'];
                            $userid = $values['userid'];
                            $username = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['userid'], 'nickname');
                            $refuserid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['refuserid'], 'nickname');
                            $reasonindetail = $values['reasonindetail'];
                            $redeempoints = $values['redeempoints'];
                            $masterlog = true;
                            $earnpoints = $values['earnedpoints'];
                            $user_deleted = true;
                            $order_status_changed = true;
                            $csvmasterlog = true;
                            $nominatedpoints = $values['nomineepoints'];
                            $nomineeid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['nomineeid'], 'nickname');
                            $usernickname = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['userid'], 'nickname');
                            $eventname = RSPointExpiry::rs_function_to_display_log($csvmasterlog, $user_deleted, $order_status_changed, $earnpoints, $order, $checkpoints, $productid, $orderid, $variationid, $userid, $refuserid, $reasonindetail, $redeempoints, $masterlog, $nomineeid, $usernickname, $nominatedpoints);
                        } else {
                            if (!empty($values['totalvalue'])) {
                                if (get_option('rs_round_off_type') == '1') {
                                    $total = $values['totalvalue'];
                                } else {
                                    $total = number_format($values['totalvalue']);
                                }
                            } else {
                                $total = $values['totalvalue'];
                            }

                            $getusernickname_masterlog_exp = get_user_meta($values['userid'], 'nickname', true);
                            if ($getusernickname_masterlog_exp == '') {
                                $getusernickname_masterlog_exp = $values['userid'];
                            }

                            $username = $getusernickname_masterlog_exp;
                            $earnpoints = $total;
                            $redeempoints = $total;
                            $eventname = $values['eventname'];
                            $values['earneddate'] = $values['date'];
                        }
                        $data[] = array(
                            'user_name' => $username,
                            'points' => $earnpoints != '0' ? $earnpoints : $redeempoints,
                            'event' => $eventname,
                            'date' => $values['earneddate'],
                        );
                        $export_masterlog_heading = "User Name,Points,Event,Date" . "\n";
                    }
                }
            } else {
                //masterlog selected users
                if ($list_users_masterlog_export != NULL) {
                    foreach ($datas as $values) {
                        if (in_array($values["userid"], $list_users_masterlog_export)) {
                            if ($i % 2 != 0) {
                                $name = 'alternate';
                            } else {
                                $name = '';
                            }
                            if ($values != '') {
                                if (isset($values['earnedpoints'])) {
                                    $orderid = $values['orderid'];
                                    $order = new WC_Order($orderid);
                                    $checkpoints = $values['checkpoints'];
                                    $productid = $values['productid'];
                                    $variationid = $values['variationid'];
                                    $userid = $values['userid'];
                                    $username = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['userid'], 'nickname');
                                    $refuserid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['refuserid'], 'nickname');
                                    $reasonindetail = $values['reasonindetail'];
                                    $redeempoints = $values['redeempoints'];
                                    $masterlog = true;
                                    $earnpoints = $values['earnedpoints'];
                                    $user_deleted = true;
                                    $order_status_changed = true;
                                    $csvmasterlog = true;
                                    $nomineeid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['nomineeid'], 'nickname');
                                    $nominatedpoints = $values['nomineepoints'];
                                    $usernickname = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['userid'], 'nickname');
                                    $eventname = RSPointExpiry::rs_function_to_display_log($csvmasterlog, $user_deleted, $order_status_changed, $earnpoints, $order, $checkpoints, $productid, $orderid, $variationid, $userid, $refuserid, $reasonindetail, $redeempoints, $masterlog, $nomineeid, $usernickname, $nominatedpoints);
                                } else {
                                    if (!empty($values['totalvalue'])) {
                                        if (get_option('rs_round_off_type') == '1') {
                                            $total = $values['totalvalue'];
                                        } else {
                                            $total = number_format($values['totalvalue']);
                                        }
                                    } else {
                                        $total = $values['totalvalue'];
                                    }

                                    $getusernickname_masterlog_exp = get_user_meta($values['userid'], 'nickname', true);
                                    if ($getusernickname_masterlog_exp == '') {
                                        $getusernickname_masterlog_exp = $values['userid'];
                                    }

                                    $username = $getusernickname_masterlog_exp;
                                    $earnpoints = $total;
                                    $redeempoints = $total;
                                    $eventname = $values['eventname'];
                                    $values['earneddate'] = $values['date'];
                                }
                                $data[] = array(
                                    'user_name' => $username,
                                    'points' => $earnpoints != '0' ? $earnpoints : $redeempoints,
                                    'event' => $eventname,
                                    'date' => $values['earneddate'],
                                );
                                $export_masterlog_heading = "User Name,Points,Event,Date" . "\n";
                            }
                        }
                    }
                }
            }
        }

        if (isset($_POST['rs_export_master_log_csv'])) {
            ob_end_clean();
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=reward_points_masterlog" . date("Y-m-d") . ".csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $export_masterlog_heading;
            self::outputCSV($data);
            exit();
        }
    }

    public static function outputCSV($data) {
        $output = fopen("php://output", "w");
        foreach ($data as $row) {
            fputcsv($output, $row); // here you can change delimiter/enclosure
        }
        fclose($output);
    }

    public static function selected_option_masterlog_export_callback() {
        global $wpdb; // this is how you get access to the database
        if (isset($_POST['export_masterlog_type'])) {
            $export_masterloguser_type_value = $_POST['export_masterlog_type'];
            update_option('selected_user_type_masterlog', $export_masterloguser_type_value);
        }
        exit();
    }

    public static function selected_users_for_export_masterlog_callback() {
        global $wpdb; // this is how you get access to the database
        $rs_selected_users_export_masterlog = $_POST['selected_users_masterlog_export'];
        if (!is_array($rs_selected_users_export_masterlog)) {
            $rs_selected_users_export_masterlog = explode(',', $rs_selected_users_export_masterlog);
        }
        update_option('rs_selected_userlist_masterlog_export', $rs_selected_users_export_masterlog);
    }

    public static function rs_list_all_points_log() {
        ?>

        <style type="text/css">
            p.submit {
                display:none;
            }
            #mainforms {
                display:none;
            }
        </style>

        <?php
        $newwp_list_table_for_users = new WP_List_Table_for_Master_Log();
        $newwp_list_table_for_users->prepare_items();
        echo '<tr valign ="top">
            <td class="forminp forminp-select">
                <input type="submit" id="rs_export_master_log_csv" name="rs_export_master_log_csv" value="Export Master Log as CSV"/>
            </td>
        </tr></p>';
        $newwp_list_table_for_users->search_box('Search', 'search_id');

        $newwp_list_table_for_users->display();
    }

}

new RSFunctionForMasterLog();
