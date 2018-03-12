<?php
/**
 * This is used to create listing table.
 * 
 * @link       https://github.com/faiyazalam
 *
 * @package    User_Login_History
 * @subpackage User_Login_History/admin
 * @author     Er Faiyaz Alam
 * @access private
 */
if (!class_exists('Faulh_Public_List_Table')) {

    class Faulh_Public_List_Table {

        const DEFALUT_LIMIT = 20;
        const DEFALUT_PAGE_NUMBER = 1;
        const DEFALUT_QUERY_ARG_PAGE_NUMBER = 'pagenum';
        const DEFAULT_TABLE_TIMEZONE = 'UTC';

        /**
         * The ID of this plugin.
         *
         * @access   private
         * @var      string    $plugin_name
         */
        private $plugin_name;

        /**
         * Number of records to be fetched from table.
         *
         * @access   private
         * @var      string|int $limit
         */
        private $limit;

        /**
         * Page number
         *
         * @access   private
         * @var      string|int $page_number
         */
        private $page_number;

        /**
         * Holds the link of pagination.
         *
         * @access   private
         * @var      string $pagination_links
         */
        private $pagination_links;

        /**
         * Holds the records.
         *
         * @access   private
         * @var      mixed $items
         */
        private $items;

        /**
         * Holds the main table name.
         *
         * @access   private
         * @var      string $table
         */
        private $table;

        /**
         * Holds the list of printable column names.
         *
         * @access   private
         * @var      array $allowed_columns
         */
        private $allowed_columns;

        /**
         * Holds the timezone to be used in table.
         *
         * @access   private
         * @var      string    $plugin_name    The ID of this plugin.
         */
        private $table_timezone;

        /**
         * Holds the date format to be used in table.
         *
         * @access   private
         * @var      string    $table_date_format
         */
        private $table_date_format;

        /**
         * Holds the time format to be used in table.
         *
         * @access   private
         * @var      string    $table_time_format
         */
        private $table_time_format;

        /**
         * Initialize the class and set its properties.
         *
         * @param      string    $plugin_name       The name of this plugin.
         */
        public function __construct($plugin_name) {
            $this->plugin_name = $plugin_name;
            $this->page_number = !empty($_REQUEST[self::DEFALUT_QUERY_ARG_PAGE_NUMBER]) ? absint($_REQUEST[self::DEFALUT_QUERY_ARG_PAGE_NUMBER]) : self::DEFALUT_PAGE_NUMBER;
            $this->set_table_name();
            $this->set_table_timezone();
        }

        /**
         * Get date-time format
         * 
         * @return string
         */
        public function get_table_date_time_format() {
            return $this->get_table_date_format() . " " . $this->get_table_time_format();
        }

        /**
         * Set date format.
         * 
         * @param string $format The date format.
         */
        public function set_table_date_format($format = "") {
            $this->table_date_format = $format;
        }

        /**
         * Get date format.
         * 
         * @return string
         */
        public function get_table_date_format() {
            return !empty($this->table_date_format) ? $this->table_date_format : "";
        }

        /**
         * Set time format.
         * 
         * @param type $format The time format.
         */
        public function set_table_time_format($format = "") {
            $this->table_time_format = $format;
        }

        /**
         * Get time format.
         * 
         * @return string
         */
        public function get_table_time_format() {
            return !empty($this->table_time_format) ? $this->table_time_format : "";
        }

        /**
         * Set table name.
         * 
         */
        private function set_table_name() {
            global $wpdb;
            $this->table = $wpdb->prefix . FAULH_TABLE_NAME;
        }

        /**
         * Set limit
         * 
         * @param type $limit
         */
        public function set_limit($limit = false) {
            $this->limit = $limit ? absint($limit) : self::DEFALUT_LIMIT;
        }

        /**
         * Prepare the items.
         * 
         */
        public function prepare_items() {
            $this->items = $this->get_rows();

            $this->pagination_links = paginate_links(array(
                'base' => add_query_arg(self::DEFALUT_QUERY_ARG_PAGE_NUMBER, '%#%'),
                'format' => '',
                'prev_text' => esc_html__('&laquo;', 'faulh'),
                'next_text' => esc_html__('&raquo;', 'faulh'),
                'total' => ceil($this->record_count() / $this->limit), //total pages
                'current' => $this->page_number
            ));
        }

        /**
         * Prepare the where query.
         * 
         * @return string
         */
        public function prepare_where_query() {
            $where_query = '';

            $fields = array(
                'user_id',
                'username',
                'browser',
                'ip_address',
                'timezone',
                'country_name',
                'operating_system',
                'login_status',
            );

            foreach ($fields as $field) {
                if (!empty($_GET[$field])) {
                    $where_query .= " AND `FaUserLogin`.`$field` = '" . esc_sql($_GET[$field]) . "'";
                }
            }

            if (!empty($_GET['role'])) {

                if ('superadmin' == $_GET['role']) {
                    $site_admins = get_super_admins();
                    $site_admins_str = implode("', '", $site_admins);
                    $where_query .= " AND `FaUserLogin`.`username` IN ('$site_admins_str')";
                } else {
                    $where_query .= " AND `UserMeta`.`meta_value` LIKE '%" . esc_sql($_GET['role']) . "%'";
                }
            }

            if (!empty($_GET['old_role'])) {
                if ('superadmin' == $_GET['old_role']) {
                    $where_query .= " AND `FaUserLogin`.`is_super_admin` LIKE '1'";
                } else {
                    $where_query .= " AND `FaUserLogin`.`old_role` LIKE '%" . esc_sql($_GET['old_role']) . "%'";
                }
            }

            if (!empty($_GET['date_type'])) {
                $date_type = esc_sql($_GET['date_type']);

                if (in_array($date_type, array('login', 'logout', 'last_seen'))) {
                    if (!empty($_GET['date_from'])) {
                        $where_query .= " AND `FaUserLogin`.`time_$date_type` >= '" . esc_sql($_GET['date_from']) . " 00:00:00'";
                    }

                    if (!empty($_GET['date_to'])) {
                        $where_query .= " AND `FaUserLogin`.`time_$date_type` <= '" . esc_sql($_GET['date_to']) . " 23:59:59'";
                    }
                }
            }

            $where_query = apply_filters('faulh_public_prepare_where_query', $where_query);
            return $where_query;
        }

        /**
         * Retrieve rows
         *
         * @param int $per_page
         * @param int $page_number
         *
         * @access   public
         * @return mixed
         */
        public function get_rows() {
            global $wpdb;
            $sql = " SELECT"
                    . " FaUserLogin.*, "
                    . " UserMeta.meta_value, TIMESTAMPDIFF(SECOND,FaUserLogin.time_login,FaUserLogin.time_last_seen) as duration"
                    . " FROM " . $this->table . "  AS FaUserLogin"
                    . " LEFT JOIN $wpdb->usermeta AS UserMeta ON ( UserMeta.user_id=FaUserLogin.user_id"
                    . " AND UserMeta.meta_key REGEXP '^wp([_0-9]*)capabilities$' )"
                    . " WHERE 1 ";

            $where_query = $this->prepare_where_query();
            if ($where_query) {
                $sql .= $where_query;
            }
            //   $sql .= ' GROUP BY FaUserLogin.id';

            if (!empty($_REQUEST['orderby'])) {
                $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
                $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
            } else {
                $sql .= ' ORDER BY FaUserLogin.time_login DESC';
            }

            if ($this->limit > 0) {
                $sql .= " LIMIT $this->limit";
                $sql .= ' OFFSET   ' . ( $this->page_number - 1 ) * $this->limit;
                ;
            }
            $result = $wpdb->get_results($sql, 'ARRAY_A');
            if ("" != $wpdb->last_error) {

                Faulh_Error_Handler::error_log("last error:" . $wpdb->last_error . " last query:" . $wpdb->last_query, __LINE__, __FILE__);
            }

            return $result;
        }

        /**
         * Count the records.
         * 
         * @global type $wpdb
         * @return string The number of records found.
         */
        public function record_count() {
            global $wpdb;
            $sql = " SELECT"
                    . " COUNT(FaUserLogin.id) as total "
                    . " FROM " . $this->table . "  AS FaUserLogin"
                    . " LEFT JOIN $wpdb->usermeta AS UserMeta ON ( UserMeta.user_id=FaUserLogin.user_id"
                    . " AND UserMeta.meta_key REGEXP '^wp([_0-9]*)capabilities$' )"
                    . " WHERE 1 ";

            $where_query = $this->prepare_where_query();
            if ($where_query) {
                $sql .= $where_query;
            }
            //  $sql .= ' GROUP BY FaUserLogin.id';
            $result = $wpdb->get_var($sql);
            if ("" != $wpdb->last_error) {
                Faulh_Error_Handler::error_log("last error:" . $wpdb->last_error . " last query:" . $wpdb->last_query, __LINE__, __FILE__);
            }
            return $result;
        }

        /**
         *  Associative array of columns
         *
         * @access public
         * @return array
         */
        public function get_columns() {
            $columns = array(
                'user_id' => esc_html__('User Id', 'faulh'),
                'username' => esc_html__('Username', 'faulh'),
                'role' => esc_html__('Current Role', 'faulh'),
                'old_role' => "<span title='" . esc_attr__('Role while user gets loggedin', 'faulh') . "'>" . esc_html__('Old Role(?)', 'faulh') . "</span>",
                'ip_address' => esc_html__('IP Address', 'faulh'),
                'browser' => esc_html__('Browser', 'faulh'),
                'operating_system' => esc_html__('Platform', 'faulh'),
                'country_name' => esc_html__('Country', 'faulh'),
                'duration' => esc_html__('Duration', 'faulh'),
                'time_last_seen' => "<span title='" . esc_attr__('Last seen time in the session', 'faulh') . "'>" . esc_html__('Last Seen(?)', 'faulh') . "</span>",
                'timezone' => esc_html__('Timezone', 'faulh'),
                'time_login' => esc_html__('Login', 'faulh'),
                'time_logout' => esc_html__('Logout', 'faulh'),
                'user_agent' => esc_html__('User Agent', 'faulh'),
                'login_status' => esc_html__('Login Status', 'faulh'),
            );
            $columns = apply_filters('faulh_public_get_columns', $columns);
            return $columns;
        }

        /**
         * Columns to make sortable.
         *
         * @access public
         * @return array
         */
        public function get_sortable_columns() {
            $sortable_columns = array(
                'user_id' => array('user_id', true),
                'username' => array('username', true),
                'old_role' => array('old_role', true),
                'time_login' => array('time_login', TRUE),
                'time_logout' => array('time_logout', false),
                'browser' => array('browser', true),
                'operating_system' => array('operating_system', false),
                'country_name' => array('country_name', false),
                'time_last_seen' => array('time_last_seen', false),
                'timezone' => array('timezone', false),
                'user_agent' => array('user_agent', false),
                'login_status' => array('login_status', false),
                'duration' => array('duration', false),
            );

            $sortable_columns = apply_filters('faulh_public_get_sortable_columns', $sortable_columns);
            return $sortable_columns;
        }

        /**
         * Display the pagination link.
         * 
         * @access private
         */
        private function display_pagination() {
            echo $this->pagination_links;
        }

        /**
         * Get the list of printable columns.
         * 
         * @access public
         * @return type
         */
        public function get_allowed_columns() {
            return empty($this->allowed_columns) ? FALSE : $this->allowed_columns;
        }

        /**
         * Set the columns to print on table.
         * This is used in shortcode.
         * 
         * @access public
         * @param type $columns
         * @return type
         */
        public function set_allowed_columns($columns = array()) {
            $columns = is_array($columns) ? $columns : (is_string($columns) ? explode(',', $columns) : array());
            $all_columns = $this->get_columns();

            foreach ($columns as $column) {
                $column = trim($column);
                if (isset($all_columns[$column])) {
                    $this->allowed_columns[] = $column;
                }
            }
        }

        /**
         * Print the column headers.
         * 
         * @access public
         */
        public function print_column_headers() {
            $allowed_columns = $this->get_allowed_columns();
            $columns = $this->get_columns();
            $sortable_columns = $this->get_sortable_columns();

            $requested_order = !empty($_GET['order']) ? $_GET['order'] : "";
            $page_number_string = !empty($_GET[self::DEFALUT_QUERY_ARG_PAGE_NUMBER]) ? "&" . self::DEFALUT_QUERY_ARG_PAGE_NUMBER . "=" . $this->page_number : "";
            //print only allowed column headers
            foreach ($allowed_columns as $allowed_column) {
                $direction = $hover = '';
//add sorting link to sortable column only
                if (isset($sortable_columns[$allowed_column])) {
                    //defaul order by field
                    $orderby = isset($sortable_columns[$allowed_column][0]) ? $sortable_columns[$allowed_column][0] : $allowed_column;
                    //defaul order direction

                    if (isset($_GET['orderby']) && isset($sortable_columns[$allowed_column][0]) && $sortable_columns[$allowed_column][0] == $_GET['orderby']) {
                        if ($requested_order) {
                            //direction based on URL
                            $direction = $requested_order == 'asc' ? '&uarr;' : '&darr;';
                            //reverse the order
                            $order = $requested_order == 'asc' ? 'desc' : 'asc';
                        }
                    } else {
                        $order = isset($sortable_columns[$allowed_column][1]) && $sortable_columns[$allowed_column][1] ? 'desc' : 'asc';
                    }
                    $hover = $order == 'asc' ? '&uarr;' : '&darr;';
                    $column_header = "<a class='sorting_link' href='?orderby=$orderby&order={$order}{$page_number_string}'>$columns[$allowed_column]<span class='sorting_hover'>$hover</span><span class='sorting'>$direction</span></a>";
                } else {
                    $column_header = $columns[$allowed_column];
                }
                echo "<th>$column_header</th>";
            }
        }

        /**
         * Display the listing table.
         * 
         * @access public
         */
        public function display() {
            $allowed_columns = $this->get_allowed_columns();
            if (empty($allowed_columns)) {
                esc_html_e('No columns is selected to display.', 'faulh');
                return;
            }
            ?>
            <table>
                <thead>
                    <tr>
            <?php $this->print_column_headers(); ?>
                    </tr>
                </thead>
                <tbody>
            <?php $this->display_rows_or_placeholder(); ?>
                </tbody>
            </table>
            <?php
            $this->display_pagination();
        }

        /**
         * Display rows or placeholder
         * 
         * @access public
         */
        public function display_rows_or_placeholder() {
            if ($this->has_items()) {
                $this->display_rows();
            } else {
                echo '<tr><td>';
                $this->no_items();
                echo '</td></tr>';
            }
        }

        /**
         * Generate the table rows
         *
         * @access public
         */
        public function display_rows() {
            foreach ($this->items as $item)
                $this->single_row($item);
        }

        /**
         * Checks if record found.
         * 
         * @return bool
         */
        public function has_items() {
            return !empty($this->items);
        }

        /**
         * Message to be displayed when there are no items
         * 
         * @access public
         */
        public function no_items() {
            esc_html_e('No items found.');
        }

        /**
         * Prints single row.
         * 
         * @access public
         * 
         * @param array $item
         */
        public function single_row($item) {
            echo '<tr>';
            $this->single_row_columns($item);
            echo '</tr>';
        }

        /**
         * Prints the column value.
         * 
         * @access public
         * @param array $item
         */
        public function single_row_columns($item) {
            $allowed_columns = $this->get_allowed_columns();
            foreach ($allowed_columns as $column_name) {
                echo "<td>" . $this->column_default($item, $column_name) . "</td>";
            }
        }

        /**
         * Set the timezone to be used for table.
         * @access public
         * @param string $timezone
         */
        public function set_table_timezone($timezone = '') {
            $this->table_timezone = $timezone;
        }

        /**
         * Get the timezone to be used for table.
         * @access public
         * @param string $timezone
         */
        public function get_table_timezone() {
            return $this->table_timezone ? $this->table_timezone : self::DEFAULT_TABLE_TIMEZONE;
        }

        /**
         * Render a column.
         *
         * @access public
         * @param array $item
         * @param string $column_name
         *
         * @return mixed
         */
        public function column_default($item, $column_name) {
            $timezone = $this->get_table_timezone();
            $unknown = 'unknown';
            $new_column_data = apply_filters('manage_faulh_public_custom_column', '', $item, $column_name);
            switch ($column_name) {
                case 'user_id':
                    if (!$item[$column_name]) {
                        return $unknown;
                    }
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'username':
                    if (!$item['user_id']) {
                        return esc_html($item[$column_name]);
                    }
                    $profile_link = get_edit_user_link($item['user_id']);
                    if($profile_link)
                    {
                          return "<a href= '$profile_link'>$item[$column_name]</a>"; 
                    }
                     return esc_html($item[$column_name]);
                case 'role':
                    if (!$item['user_id']) {
                        return $unknown;
                    }
                    $user_data = get_userdata($item['user_id']);
                    return !empty($user_data->roles) ? implode(',', $user_data->roles) : $unknown;
                case 'old_role':
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'browser':
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'time_login':
                    return Faulh_Date_Time_Helper::convert_format(Faulh_Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone), $this->get_table_date_time_format());
                case 'time_logout':
                    if (!$item['user_id']) {
                        return $unknown;
                    }
                    return strtotime($item[$column_name]) > 0 ? Faulh_Date_Time_Helper::convert_format(Faulh_Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone), $this->get_table_date_time_format()) : esc_html__('Logged In', 'faulh');
                case 'ip_address':
                    return $item[$column_name] ? esc_html($item[$column_name]) : $unknown;
                case 'timezone':
                    return $item[$column_name] ? esc_html($item[$column_name]) : $unknown;
                case 'operating_system':
                    return $item[$column_name] ? $item[$column_name] : $unknown;

                case 'country_name':
                    $country_code = empty($item['country_code']) || $unknown == strtolower($item['country_code']) ? $unknown : $item['country_code'];
                    return in_array(strtolower($item[$column_name]), array("", $unknown)) ? $unknown : esc_html($item[$column_name] . "(" . $country_code . ")");
                case 'country_code':
                    return empty($item['country_code']) || $unknown == strtolower($item['country_code']) ? $unknown : esc_html($item['country_code']);

                case 'time_last_seen':
                    if (!$item['user_id']) {
                        return $unknown;
                    }
                    $time_last_seen_unix = strtotime($item[$column_name]);
                    $time_last_seen = Faulh_Date_Time_Helper::convert_format(Faulh_Date_Time_Helper::convert_timezone($item[$column_name], '', $timezone));
                    $human_time_diff = human_time_diff($time_last_seen_unix);
                    $is_online_str = 'offline';
                    if (Faulh_User_Tracker::LOGIN_STATUS_LOGIN == $item['login_status']) {
                        $minutes = ((time() - $time_last_seen_unix) / 60);
                        $settings = get_option($this->plugin_name . "_basics");
                        $minute_online = !empty($settings['is_status_online']) ? $settings['is_status_online'] : FAULH_DEFAULT_IS_STATUS_ONLINE_MIN;
                        $minute_idle = !empty($settings['is_status_idle']) ? $settings['is_status_idle'] : FAULH_DEFAULT_IS_STATUS_IDLE_MIN;
                        if ($minutes <= $minute_online) {
                            $is_online_str = 'online';
                        } elseif ($minutes <= $minute_idle) {
                            $is_online_str = 'idle';
                        }
                    }

                    return "<div class='is_status_$is_online_str' title = '$time_last_seen'>" . $human_time_diff . " " . esc_html__('ago', 'faulh') . '</div>';

                case 'user_agent':
                    return $item[$column_name] ? esc_html($item[$column_name]) : $unknown;
                case 'duration':
                    $duration = human_time_diff(strtotime($item['time_login']), strtotime(Faulh_Date_Time_Helper::get_last_time($item['time_logout'], $item['time_last_seen'])));
                    return $duration ? $duration : $unknown;
                case 'login_status':
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'site_id':
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'blog_id':
                    return $item[$column_name] ? $item[$column_name] : $unknown;
                case 'is_super_admin':
                    return $item[$column_name] ? esc_html__('Yes', 'faulh') : esc_html__('No', 'faulh');
                default:
                    if ($new_column_data) {
                        return $new_column_data;
                    }
                    return print_r($item, true); //Show the whole array for troubleshooting purposes
            }
        }

    }

}

