<?php

namespace User_Login_History\Inc\Admin;

use User_Login_History\Inc\Common\Helpers\Template as Template_Helper;

class Network_Admin_Settings {

    /**
     * The unique identifier of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    private $plugin_name;
    private $form_name;
    private $form_nonce_name;
    private $settings_name;

    /**
     * Initialize the class and set its properties.
     *
     * @access public
     * @param      array    $args       The overridden arguments.
     * @param      string    $plugin_name       The name of this plugin.
     */
    function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
        $this->form_name = $this->plugin_name . '_network_admin_setting_submit';
        $this->form_nonce_name = $this->plugin_name . '_network_admin_setting_nonce';
        $this->settings_name = $this->plugin_name . '_network_settings';
    }

    private function is_form_submitted() {
        return isset($_POST[$this->get_form_name()]) && !empty($_POST[$this->get_form_nonce_name()]) && wp_verify_nonce($_POST[$this->get_form_nonce_name()], $this->get_form_nonce_name());
    }

    /**
     * Update the settings.
     * 
     * @access private
     */
    private function update_settings() {

        $settings = array();

        if (isset($_POST['block_user'])) {
            $settings['block_user'] = 1;
        }
        if (isset($_POST['block_user_message'])) {
            $settings['block_user_message'] = sanitize_textarea_field($_POST['block_user_message']);
        }

        return !empty($settings) ? update_site_option($this->settings_name, $settings) : delete_site_option($this->settings_name);
    }

    public function get_form_name() {
        return $this->form_name;
    }

    public function get_form_nonce_name() {
        return $this->form_nonce_name;
    }

    /**
     * The callback function for the action hook - network_admin_menu.
     */
    public function add_setting_menu() {
        add_submenu_page(
                'settings.php', Template_Helper::plugin_name(), Template_Helper::plugin_name(), 'manage_options', $this->plugin_name . '-setting', array($this, 'screen')
        );
    }

    /**
     * The template file for setting page.
     * 
     * @access public
     * @return string The template file path.
     */
    public function screen() {
        require_once plugin_dir_path((__FILE__)) . 'views/settings/network-admin.php';
    }

    /**
     * Check nonce and form submission and then update the settings.
     * 
     * @access public
     */
    public function update() {
        return $this->is_form_submitted() ? $this->update_settings() : FALSE;
    }

    /**
     * Get the setting by name.
     *
     * @param $setting string optional setting name
     */
    public function get_settings($setting = '') {
        global $settings;

        if (isset($settings)) {
            if ($setting) {
                return isset($settings[$setting]) ? maybe_unserialize($settings[$setting]) : null;
            }
            return $settings;
        }

        $settings = wp_parse_args(get_site_option($this->settings_name), array(
            'block_user' => null,
            'block_user_message' => 'Please contact website administrator.',
        ));

        if ($setting) {
            return isset($settings[$setting]) ? maybe_unserialize($settings[$setting]) : null;
        }
        return $settings;
    }

}