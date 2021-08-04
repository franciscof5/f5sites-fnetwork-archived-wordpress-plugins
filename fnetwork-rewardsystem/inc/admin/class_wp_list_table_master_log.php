<?php

// Integrate WP List Table for Master Log

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_List_Table_for_Master_Log extends WP_List_Table {

    // Prepare Items
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();

        if (isset($_REQUEST['s'])) {
            $searchvalue = $_REQUEST['s'];
            $keyword = "/$searchvalue/";

            $newdata = array();
            foreach ($data as $eacharray => $value) {
                $searchfunction = preg_grep($keyword, $value);
                if (!empty($searchfunction)) {
                    $newdata[] = $data[$eacharray];
                }
            }
            usort($newdata, array(&$this, 'sort_data'));

            $perPage = 10;
            $currentPage = $this->get_pagenum();
            $totalItems = count($newdata);

            $this->set_pagination_args(array(
                'total_items' => $totalItems,
                'per_page' => $perPage
            ));

            $newdata = array_slice($newdata, (($currentPage - 1) * $perPage), $perPage);

            $this->_column_headers = array($columns, $hidden, $sortable);

            $this->items = $newdata;
        } else {
            usort($data, array(&$this, 'sort_data'));

            $perPage = 10;
            $currentPage = $this->get_pagenum();
            $totalItems = count($data);

            $this->set_pagination_args(array(
                'total_items' => $totalItems,
                'per_page' => $perPage
            ));

            $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

            $this->_column_headers = array($columns, $hidden, $sortable);

            $this->items = $data;
        }
    }

    public function get_columns() {
        $columns = array(
            'sno' => __('S.No', 'rewardsystem'),
            'user_name' => __('User Name', 'rewardsystem'),
            'points' => __('Points', 'rewardsystem'),
            'event' => __('Event', 'rewardsystem'),
            'date' => __('Date', 'rewardsystem'),
        );

        return $columns;
    }

    public function get_hidden_columns() {
        return array();
    }

    public function get_sortable_columns() {
        return array(
            'points' => array('points', false),
            'sno' => array('sno', false),
            'date' => array('date', false),
        );
    }

    private function table_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rsrecordpoints';
        $data = array();
        $i = 1;
        $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
        $subdatas = $wpdb->get_results("SELECT * FROM $table_name WHERE showmasterlog = false", ARRAY_A);
        $subdatas = $subdatas + (array) get_option('rsoveralllog');
        if (is_array($subdatas)) {
            foreach ($subdatas as $values) {
                $getuserbyid = get_user_by('id', @$values['userid']);
                if (isset($values['earnedpoints'])) {
                    if (!empty($values['earnedpoints'])) {
                        if (is_float($values['earnedpoints'])) {

                            $total = round(number_format($values['earnedpoints'], 2), $roundofftype);
                        } else {
                            $total = number_format($values['earnedpoints']);
                        }
                    } else {
                        $total = @$values['earnedpoints'];
                    }
                } else {
                    $getuserbyid = get_user_by('id', @$values['userid']);

                    if (!empty($values['totalvalue'])) {
                        if (get_option('rs_round_off_type') == '1') {

                            $total = $values['totalvalue'];
                        } else {
                            $total = number_format($values['totalvalue']);
                        }
                    } else {
                        $total = @$values['totalvalue'];
                    }
                }

                if ($values != '') {
                    if (isset($values['earnedpoints'])) {
                        $orderid = $values['orderid'];
                        $order = new WC_Order($orderid);
                        $checkpoints = $values['checkpoints'];
                        $productid = $values['productid'];
                        $variationid = $values['variationid'];
                        $userid = $values['userid'];
                        $refuserid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['refuserid'], 'nickname');
                        $reasonindetail = $values['reasonindetail'];
                        $redeempoints = $values['redeempoints'];
                        $masterlog = true;
                        $earnpoints = $values['earnedpoints'];
                        $user_deleted = true;
                        $order_status_changed = true;
                        $csvmasterlog = false;
                        $nomineeid = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['nomineeid'], 'nickname');
                        $usernickname = RSFunctionForSavingMetaValues::rewardsystem_get_user_meta($values['userid'], 'nickname');
                        $nominatedpoints = $values['nomineepoints'];
                        $eventname = RSPointExpiry::rs_function_to_display_log($csvmasterlog, $user_deleted, $order_status_changed, $earnpoints, $order, $checkpoints, $productid, $orderid, $variationid, $userid, $refuserid, $reasonindetail, $redeempoints, $masterlog, $nomineeid, $usernickname, $nominatedpoints);
                        $total = $total != '0' ? $total : $redeempoints;
                    } else {
                        $eventname = $values['eventname'];
                        $values['earneddate'] = $values['date'];
                    }
                    $data[] = array(
                        'sno' => $i,
                        'user_name' => $getuserbyid->user_login,
                        'points' => $total,
                        'event' => $eventname,
                        'date' => $values['earneddate'],
                    );
                    $i++;
                }
            }
        }
        return $data;
    }

    public function column_id($item) {
        return $item['sno'];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'sno':
            case 'user_name':
            case 'points':
            case 'event':
            case 'date':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    private function sort_data($a, $b) {

        $orderby = 'sno';
        $order = 'asc';

        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }

        $result = strnatcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }

}
