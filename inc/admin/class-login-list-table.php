<?php

namespace User_Login_History\Inc\Admin;

use User_Login_History as NS;
use User_Login_History\Inc\Common\Helpers\Db as Db_Helper;
use User_Login_History\Inc\Common\Helpers\Date_Time as Date_Time_Helper;
use User_Login_History\Inc\Admin\User_Profile;
use User_Login_History\Inc\Common\Abstracts\List_Table as List_Table_Abstract;
use User_Login_History\Inc\Common\Login_Tracker;
use User_Login_History\Inc\Common\Helpers\Template as Template_Helper;


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       http://userloginhistory.com
 * @since      1.0.0
 *
 * @author    Er Faiyaz Alam
 */
class Login_List_Table extends List_Table_Abstract {
    public function __construct($plugin_name, $version, $plugin_text_domain) {
        $args = array(
            'singular' => $plugin_name . '_user_login', //singular name of the listed records
            'plural' => $plugin_name . '_user_logins', //plural name of the listed records
        );
        parent::__construct($plugin_name, $version, $plugin_text_domain, $args);
        $this->table = NS\PLUGIN_TABLE_FA_USER_LOGINS;

        
        
         $this->delete_action = $this->_args['singular'] . "_delete";
    }



    /**
     * Prepares the where query.
     *
     * @access public
     * @return string
     */
    public function prepare_where_query() {

        $where_query = '';

        $fields = array(
            'user_id',
            'username',
            'browser',
            'operating_system',
            'ip_address',
            'timezone',
            'country_name',
            'old_role',
        );

        foreach ($fields as $field) {
            if (!empty($_GET[$field])) {
                $where_query .= " AND `FaUserLogin`.`$field` = '" . esc_sql(trim($_GET[$field])) . "'";
            }
        }

        if (!empty($_GET['role'])) {
            $where_query .= " AND `UserMeta`.`meta_value` LIKE '%" . esc_sql($_GET['role']) . "%'";
        }


        if (!empty($_GET['date_type'])) {
            $UserProfile = new User_Profile($this->plugin_name, $this->version, $this->plugin_text_domain);
            $input_timezone = $UserProfile->get_user_timezone();
            $date_type = $_GET['date_type'];
            if (in_array($date_type, array('login', 'logout', 'last_seen'))) {

                if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
                    $date_type = esc_sql($date_type);
                    $date_from = Date_Time_Helper::convert_timezone($_GET['date_from'] . " 00:00:00", $input_timezone);
                    $date_to = Date_Time_Helper::convert_timezone($_GET['date_to'] . " 23:59:59", $input_timezone);
                    $where_query .= " AND `FaUserLogin`.`time_$date_type` >= '" . esc_sql($date_from) . "'";
                    $where_query .= " AND `FaUserLogin`.`time_$date_type` <= '" . esc_sql($date_to) . "'";
                } else {
                    unset($_GET['date_from']);
                    unset($_GET['date_to']);
                }
            }
        }


        if (!empty($_GET['login_status'])) {
            $login_status = $_GET['login_status'];
            $login_status_value = "unknown" == $login_status ? "" : esc_sql($login_status);
            $where_query .= " AND `FaUserLogin`.`login_status` = '" . $login_status_value . "'";
        }

        $where_query = apply_filters('faulh_admin_prepare_where_query', $where_query);
        return $where_query;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @access public 
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => esc_html__('Delete Selected Records', 'faulh'),
            'bulk-delete-all-admin' => esc_html__('Delete All Records', 'faulh'),
        );

        return $actions;
    }

    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'user_id' => esc_html__('User ID', $this->plugin_text_domain),
            'username' => esc_html__('Username', $this->plugin_text_domain),
            'role' => esc_html__('Role', $this->plugin_text_domain),
            'old_role' => esc_html__('Old Role', $this->plugin_text_domain),
            'browser' => esc_html__('Browser', $this->plugin_text_domain),
            'operating_system' => esc_html__('Operating System', $this->plugin_text_domain),
            'ip_address' => esc_html__('IP Address', $this->plugin_text_domain),
            'timezone' => esc_html__('Timezone', $this->plugin_text_domain),
            'country_name' => esc_html__('Country', $this->plugin_text_domain),
            'user_agent' => esc_html__('User Agent', $this->plugin_text_domain),
            'duration' => esc_html__('Duration', $this->plugin_text_domain),
            'time_last_seen' => esc_html__('Last Seen', $this->plugin_text_domain),
            'time_login' => esc_html__('Login', $this->plugin_text_domain),
            'time_logout' => esc_html__('Logout', $this->plugin_text_domain),
            'login_status' => esc_html__('Login Status', $this->plugin_text_domain),
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $columns = array(
            'user_id' => array('user_id', true),
            'username' => array('username', true),
            'old_role' => array('old_role', true),
            'time_login' => array('time_login', false),
            'time_logout' => array('time_logout', false),
            'browser' => array('browser', false),
            'operating_system' => array('operating_system', false),
            'country_name' => array('country_name', false),
            'time_last_seen' => array('time_last_seen', false),
            'timezone' => array('timezone', false),
            'user_agent' => array('user_agent', false),
            'login_status' => array('login_status', false),
            'duration' => array('duration', false),
        );

        return $columns;
    }

    /**
     * Method for name column
     * 
     * @access   public
     * @param array $item an array of DB data
     * @return string
     */
   
    public function column_username($item) {
        $username = $this->is_empty($item['username']) ? $this->unknown_symbol : esc_html($item['username']);
        if ($this->is_empty($item['user_id'])) {
            $title = $username;
        } else {
            $edit_link = get_edit_user_link($item['user_id']);
            $title = !empty($edit_link) ? "<a href='" . $edit_link . "'>" . $username . "</a>" : '<strong>' . $username . '</strong>';
        }

        $delete_nonce = wp_create_nonce($this->delete_action_nonce);
        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=%s&record_id=%s&_wpnonce=%s">Delete</a>', esc_attr($_REQUEST['page']), $this->delete_action, absint($item['id']), $delete_nonce),
        );
        return $title . $this->row_actions($actions);
    }

    /**
     * Render the bulk edit checkbox
     * 
     * @access   public
     * @param array $item
     * @return string
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="bulk-action-ids[]" value="%s" />', $item['id']);
    }

    public function process_bulk_action() {
         $this->set_message(esc_html__('Please try again.', $this->plugin_text_domain));
        switch ($this->current_action()) {
            case 'bulk-delete':
                $status = Db_Helper::delete_rows_by_table_and_ids($this->table, $_POST['bulk-action-ids']);
                if ($status) {
                    $this->set_message(esc_html__('Selected record(s) deleted.', $this->plugin_text_domain));
                }
                break;
            case 'bulk-delete-all-admin':
                $status = Db_Helper::truncate_table($this->table);
                if ($status) {
                    $this->set_message(esc_html__('All record(s) deleted.', $this->plugin_text_domain));
                }
                break;
            default:
                $status = FALSE;
                break;
        }
        return $status;
    }

    public function process_single_action() {
        if (empty($_GET['record_id'])) {
            return;
        }

        $id = absint($_GET['record_id']);
        $this->set_message(esc_html__('Please try again.', $this->plugin_text_domain));
        switch ($this->current_action()) {
            case $this->delete_action:
                $status = Db_Helper::delete_rows_by_table_and_ids($this->table, array($id));
                if ($status) {
                    $this->set_message(esc_html__('Record deleted.', $this->plugin_text_domain));
                }
                break;

            default:
                $status = FALSE;
                break;
        }

        return $status;
    }
    
    public function column_time_last_seen($item) {
        $column_name = 'time_last_seen';

        $time_last_seen_unix = strtotime($item[$column_name]);

        if ($this->is_empty($item['user_id']) || !($time_last_seen_unix > 0)) {
            return $this->get_unknown_symbol();
        }

        $timezone = $this->get_timezone();
        $time_last_seen = Date_Time_Helper::convert_format(Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone));

        if (!$time_last_seen) {
            return $this->get_unknown_symbol();
        }

        $human_time_diff = human_time_diff($time_last_seen_unix);
        $is_online_str = 'offline';

        if (in_array($item['login_status'], array("", Login_Tracker::LOGIN_STATUS_LOGIN))) {
            $minutes = ((time() - $time_last_seen_unix) / 60);
            $settings = get_option($this->plugin_name . "_basics");
            $minute_online = !empty($settings['is_status_online']) ? absint($settings['is_status_online']) : NS\DEFAULT_IS_STATUS_ONLINE_MIN;
            $minute_idle = !empty($settings['is_status_idle']) ? absint($settings['is_status_idle']) : NS\DEFAULT_IS_STATUS_IDLE_MIN;
            if ($minutes <= $minute_online) {
                $is_online_str = 'online';
            } elseif ($minutes <= $minute_idle) {
                $is_online_str = 'idle';
            }
        }


        return "<div class='is_status_$is_online_str' title = '$time_last_seen'>" . $human_time_diff . " " . esc_html__('ago', 'faulh') . '</div>';
    }

    public function column_default($item, $column_name) {
        $timezone = $this->get_timezone();


        $new_column_data = apply_filters('manage_faulh_admin_custom_column', '', $item, $column_name);
        $country_code = in_array(strtolower($item['country_code']), array("", $this->get_unknown_symbol())) ? $this->get_unknown_symbol() : $item['country_code'];

        switch ($column_name) {

            case 'user_id':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : absint($item[$column_name]);

            case 'username':
                return $this->is_empty($item['username']) ? $this->get_unknown_symbol() : esc_html($item['username']);

            case 'role':

                if ($this->is_empty($item['user_id'])) {
                    return $this->get_unknown_symbol();
                }


                if (is_network_admin()) {
                    switch_to_blog($item['blog_id']);
                    $user_data = get_userdata($item['user_id']);
                    restore_current_blog();
                } else {
                    $user_data = get_userdata($item['user_id']);
                }





                return $this->is_empty($user_data->roles) ? $this->get_unknown_symbol() : esc_html(implode(',', $user_data->roles));

            case 'old_role':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);

            case 'browser':

                if ($this->is_empty($item[$column_name])) {
                    return $this->get_unknown_symbol();
                }

                if (empty($item['browser_version'])) {
                    return esc_html($item[$column_name]);
                }

                return esc_html($item[$column_name] . " (" . $item['browser_version'] . ")");

            case 'ip_address':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);

            case 'timezone':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);


            case 'country_name':

                if ($this->is_empty($item[$column_name])) {
                    return $this->get_unknown_symbol();
                }

                if (empty($item['country_code'])) {
                    return esc_html($item[$column_name]);
                }

                return esc_html($item[$column_name] . " (" . $item['country_code'] . ")");


            case 'country_code':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);

            case 'operating_system':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);


            case 'time_login':
                if (!(strtotime($item[$column_name]) > 0)) {
                    return $this->get_unknown_symbol();
                }
                $time_login = Date_Time_Helper::convert_format(Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone));
                return $time_login ? $time_login : $this->get_unknown_symbol();

            case 'time_logout':
                if ($this->is_empty($item['user_id']) || !(strtotime($item[$column_name]) > 0)) {
                    return $this->get_unknown_symbol();
                }
                $time_logout = Date_Time_Helper::convert_format(Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone));
                return $time_logout ? $time_logout : $this->get_unknown_symbol();



            case 'time_last_seen':

                $time_last_seen_unix = strtotime($item[$column_name]);
                if ($this->is_empty($item['user_id']) || !($time_last_seen_unix > 0)) {
                    return $this->get_unknown_symbol();
                }
                $time_last_seen = Date_Time_Helper::convert_format(Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone));

                if (!$time_last_seen) {
                    return $this->get_unknown_symbol();
                }

                return human_time_diff($time_last_seen_unix) . " " . esc_html__('ago', 'faulh') . " ($time_last_seen)";

            case 'user_agent':
                return $this->is_empty($item[$column_name]) ? $this->get_unknown_symbol() : esc_html($item[$column_name]);

            case 'duration':
                if ($this->is_empty($item['time_login']) || !(strtotime($item['time_login']) > 0)) {
                    return $this->get_unknown_symbol();
                }

                if ($this->is_empty($item['time_last_seen']) || !(strtotime($item['time_login']) > 0)) {
                    return $this->get_unknown_symbol();
                }
                return human_time_diff(strtotime($item['time_login']), strtotime($item['time_last_seen']));

            case 'login_status':
                $login_statuses = Template_Helper::login_statuses();
                return !empty($login_statuses[$item[$column_name]]) ? $login_statuses[$item[$column_name]] : $this->get_unknown_symbol();

            case 'blog_id':
                return !empty($item[$column_name]) ? (int) $item[$column_name] : $this->get_unknown_symbol();

            case 'is_super_admin':
                $super_admin_statuses = Template_Helper::super_admin_statuses();
                return $super_admin_statuses[$item[$column_name] ? 'yes' : 'no'];

            default:
                if ($new_column_data) {
                    return $new_column_data;
                }
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

}
