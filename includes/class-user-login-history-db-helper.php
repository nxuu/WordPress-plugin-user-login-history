<?php

/**
 * User_Login_History_DB_Helper.
 *
 * @link       https://github.com/faiyazalam
 * @package    User_Login_History
 * @subpackage User_Login_History/includes
 * @author     Er Faiyaz Alam
 * @access private
 */
class User_Login_History_DB_Helper {

    /**
     * Retrieve the blog name by blog id.
     * 
     * @global type $wpdb
     * @param int|string $id The id of the blog.
     * @return string|null If success, the name of the blog otherwise null.
     */
    static public function get_blog_name_by_id($id = NULL) {
        if (!$id) {
            return '';
        }
        global $wpdb;
        return $wpdb->get_var("SELECT CONCAT(domain,path) AS blog_name FROM $wpdb->blogs WHERE blog_id = $id ");
    }

    /**
     * Get blog by blog id and network id.
     * 
     * @global type $wpdb
     * @param int $blog_id 
     * @param int $network_id
     * @return array Database query result. Array indexed from 0 by SQL result row number.
     */
    static public function get_blog_by_id_and_network_id($blog_id, $network_id) {
        global $wpdb;
        $where = '';
        if ($blog_id) {
            $where .= " AND `blog_id` = " . absint($blog_id);
        }

        if ($network_id) {
            $where .= " AND `site_id` = " . absint($network_id);
        }

        $sql = "SELECT blog_id FROM $wpdb->blogs WHERE 1 $where";
        $result = $wpdb->get_col($sql);
        if ($wpdb->last_error) {
            User_Login_History_Error_Handler::error_log("last error:" . $wpdb->last_error . " last query:" . $wpdb->last_query, __LINE__, __FILE__);
        }
        return $result;
    }

}
