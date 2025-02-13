<?php

namespace User_Login_History\Inc\Admin;

use User_Login_History as NS;
use User_Login_History\Inc\Common\Helpers\Db as Db_Helper;
use User_Login_History\Inc\Common\Helpers\Date_Time as Date_Time_Helper;
use User_Login_History\Inc\Admin\User_Profile;
use User_Login_History\Inc\Common\Abstracts\List_Table as List_Table_Abstract;
use User_Login_History\Inc\Common\Interfaces\Admin_Csv as Admin_Csv_Interface;
use User_Login_History\Inc\Common\Interfaces\Admin_List_Table as Admin_List_Table_Interface;

/**
 * Render the login listing page.
 *
 * @author    Er Faiyaz Alam
 */
final class Admin_Login_List_Table extends Login_List_Table implements Admin_Csv_Interface, Admin_List_Table_Interface {

    /**
     * Return the records.
     * 
     * @access   public
     * @param int $per_page
     * @param int $page_number
     * @global object $wpdb
     * @return mixed
     */
    public function get_rows($per_page = 20, $page_number = 1) {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;
        $sql = " SELECT"
                . " FaUserLogin.*, "
                . " UserMeta.meta_value as role, TIMESTAMPDIFF(SECOND,FaUserLogin.time_login,FaUserLogin.time_last_seen) as duration"
                . " FROM " . $table . "  AS FaUserLogin"
                . " LEFT JOIN $wpdb->usermeta AS UserMeta ON ( UserMeta.user_id=FaUserLogin.user_id"
                . " AND UserMeta.meta_key LIKE  '" . $wpdb->prefix . "capabilities' )"
                . " WHERE 1 ";

        $where_query = $this->prepare_where_query();
        if ($where_query) {
            $sql .= $where_query;
        }
        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY id DESC';
        }

        if ($per_page > 0) {
            $sql .= " LIMIT $per_page";
            $sql .= ' OFFSET   ' . ( $page_number - 1 ) * $per_page;
        }

        return Db_Helper::get_results($sql);
    }

    /**
     * Returns the count of the records.
     * 
     * @global object $wpdb
     * @return mixed
     */
    public function record_count() {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;
        $sql = " SELECT"
                . " COUNT(FaUserLogin.id) AS total"
                . " FROM " . $table . " AS FaUserLogin"
                . " LEFT JOIN $wpdb->usermeta AS UserMeta ON ( UserMeta.user_id=FaUserLogin.user_id"
                . " AND UserMeta.meta_key LIKE '" . $wpdb->prefix . "capabilities' )"
                . " WHERE 1 ";

        $where_query = $this->prepare_where_query();

        if ($where_query) {
            $sql .= $where_query;
        }

        return Db_Helper::get_var($sql);
    }

    /**
     * @overridden
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
            'delete' => sprintf('<a href="?page=%s&action=%s&record_id=%s&_wpnonce=%s">%s</a>', esc_attr($_REQUEST['page']), $this->delete_action, absint($item['id']), $delete_nonce, esc_html__('Delete', 'faulh')),
        );
        return $title . $this->row_actions($actions);
    }

    /**
     * @overridden
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="bulk-action-ids[]" value="%s" />', $item['id']);
    }

    /**
     * Handles the bulk actions.
     */
    public function process_bulk_action() {
        $nonce = '_wpnonce';

        if (!isset($_POST[$this->get_bulk_action_form()]) || empty($_POST[$nonce]) || !wp_verify_nonce($_POST[$nonce], $this->get_bulk_action_nonce()) || !current_user_can('administrator')) {
            return;
        }

        $message = esc_html__('Please try again.', 'faulh');
        $status = FALSE;

        switch ($this->current_action()) {

            case 'bulk-delete':

                if (!empty($_POST['bulk-action-ids'])) {
                    $status = Db_Helper::delete_rows_by_table_and_ids($this->table, $_POST['bulk-action-ids']);
                    if ($status) {
                        $message = esc_html__('Selected record(s) deleted.', 'faulh');
                    }
                }

                break;

            case 'bulk-delete-all-admin':

                $status = Db_Helper::truncate_table($this->table);
                if ($status) {
                    $message = esc_html__('All record(s) deleted.', 'faulh');
                }
                break;
        }

        $this->Admin_Notice->add_notice($message, $status ? 'success' : 'error');
        wp_safe_redirect(esc_url("admin.php?page=" . $_GET['page']));
        exit;
    }

    /**
     * Handles the single action.
     */
    public function process_single_action() {
        $nonce = '_wpnonce';

        if (empty($_GET['record_id']) || empty($_GET[$nonce]) || !wp_verify_nonce($_GET[$nonce], $this->get_delete_action_nonce()) || !current_user_can('administrator')) {
            return;
        }

        $id = absint($_GET['record_id']);
        $status = FALSE;
        $message = esc_html__('Please try again.', 'faulh');
        switch ($this->current_action()) {
            case $this->delete_action:
                $status = Db_Helper::delete_rows_by_table_and_ids($this->table, array($id));
                if ($status) {
                    $message = esc_html__('Record deleted.', 'faulh');
                }
                break;
        }

        $this->Admin_Notice->add_notice($message, $status ? 'success' : 'error');
        wp_safe_redirect(esc_url("admin.php?page=" . $_GET['page']));
        exit;
    }

    /**
     * @overridden
     */
    public function get_columns() {
        return apply_filters($this->plugin_name . "_admin_login_list_get_columns", parent::get_columns());
    }

    /**
     * @overridden
     */
    public function get_sortable_columns() {
        return apply_filters($this->plugin_name . "_admin_login_list_get_columns", parent::get_sortable_columns());
    }

}
