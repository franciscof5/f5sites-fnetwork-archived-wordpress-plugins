<?php

// Integrate WP List Table for Users

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_List_Table_for_Users extends WP_List_Table {

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
            'user_email' => __('User Email', 'rewardsystem'),
            'total_points' => __('Total Points', 'rewardsystem'),
            'view' => __('View', 'rewardsystem'),
            'edit' => __('Edit', 'rewardsystem'),
        );

        return $columns;
    }

    public function get_hidden_columns() {
        return array();
    }

    public function get_sortable_columns() {
        return array('user_name' => array('user_name', false),
            'sno' => array('sno', false),
            'total_points' => array('total_points', false),
        );
    }

    private function table_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rspointexpiry';
        $data = array();
        $i = 1;
        $roundofftype = get_option('rs_round_off_type') == '1' ? '2' : '0';
        foreach (get_users() as $user) {
            $getuserbyid = get_user_by('id', $user->ID);            
            $getusermeta = $wpdb->get_results("SELECT SUM((earnedpoints-usedpoints)) as availablepoints FROM $table_name WHERE earnedpoints-usedpoints NOT IN(0) and expiredpoints IN(0) and userid=$user->ID", ARRAY_A);            
            $data[] = array(
                'sno' => $i,
                'user_name' => $getuserbyid->user_login,
                'user_email' => $getuserbyid->user_email,
                'total_points' => $getusermeta[0]['availablepoints'] != '' ? round($getusermeta[0]['availablepoints'], $roundofftype) : '0',
                'view' => "<a href=" . add_query_arg('view', $user->ID, get_permalink()) . ">View Log</a>",
                'edit' => "<a href=" . add_query_arg('edit', $user->ID, get_permalink()) . ">Edit Total Points</a>",
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
            case 'user_name':
            case 'user_email':
            case 'total_points':
            case 'view':
            case 'edit':

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
