<?php

// Integrate WP List Table for Referral Table

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_List_Table_for_Referral_Table extends WP_List_Table {

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
            'referer_name' => __('Referer User Name', 'rewardsystem'),
            'refered_person_count' => __('Refered Person Count', 'rewardsystem'),
            'total_referral_points' => __('Total Referral Points', 'rewardsystem'),
        );

        return $columns;
    }

    public function get_hidden_columns() {
        return array();
    }

    public function get_sortable_columns() {
        return array('refered_person_count' => array('refered_person_count', false),
            'sno' => array('sno', false),
            'total_points' => array('total_points', false),
        );
    }

    private function table_data() {
        $data = array();
        $i = 1;
        $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
        foreach (get_users() as $user) {
            $getuserbyid = get_user_by('id', $user->ID);
            $referreduser_count = RS_Referral_Log::get_count_of_corresponding_users($user->ID);
            $total_referral_points = RS_Referral_Log::get_totals_of_referral_persons($user->ID);
            $data[] = array(
                'sno' => $i,
                'referer_name' => $getuserbyid->user_login,
                'refered_person_count' => $referreduser_count > 0 ? "<a href=" . add_query_arg('view', $user->ID, get_permalink()) . ">$referreduser_count</a>" : "0",
                'total_referral_points' => $total_referral_points > 0 ? round($total_referral_points, $roundofftype) : '0',
            );
            $i++;
        }        
        return $data;
    }

    public function column_id($item) {
        return $item['sno'];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'sno':
            case 'referer_name':
            case 'refered_person_count':
            case 'total_referral_points':
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
