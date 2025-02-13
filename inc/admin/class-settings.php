<?php

namespace User_Login_History\Inc\Admin;

/**
 * Admin specific settings.
 */
use User_Login_History\Inc\Admin\Settings_Api;
use User_Login_History as NS;

class Settings {

    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Holds the instance of Settings_Api
     * @var Settings_Api 
     */
    private $Settings_Api;

    function __construct($plugin, $version, Settings_Api $Settings_Api) {
        $this->Settings_Api = $Settings_Api;
        $this->plugin_name = $plugin;
    }

    /**
     * Hooked with admin_init action
     */
    function admin_init() {
        $this->Settings_Api->set_sections($this->get_settings_sections());
        $this->Settings_Api->set_fields($this->get_settings_fields());
        $this->Settings_Api->admin_init();
    }

    /**
     * Hooked with admin_menu action
     */
    function admin_menu() {
        add_options_page(NS\PLUGIN_NAME, NS\PLUGIN_NAME, 'manage_options', $this->plugin_name . "-settings", array($this, 'plugin_page'));
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => $this->plugin_name . '_basics',
                'title' => esc_html__('Basic Settings', 'faulh'),
            ),
            array(
                'id' => $this->plugin_name . '_advanced',
                'title' => esc_html__('Advanced Settings', 'faulh'),
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            $this->plugin_name . '_basics' => array(
                array(
                    'name' => 'is_status_online',
                    'label' => esc_html__('Online', 'faulh'),
                    'desc' => esc_html__('Maximum number of minutes for online users. Default is', 'faulh') . " " . NS\DEFAULT_IS_STATUS_ONLINE_MIN,
                    'min' => 1,
                    'step' => '1',
                    'type' => 'number',
                    'default' => NS\DEFAULT_IS_STATUS_ONLINE_MIN,
                    'sanitize_callback' => 'absint'
                ),
                array(
                    'name' => 'is_status_idle',
                    'label' => esc_html__('Idle', 'faulh'),
                    'desc' => esc_html__('Maximum number of minutes for idle users. This should be greater than that of online users. Default is', 'faulh') . " " . NS\DEFAULT_IS_STATUS_IDLE_MIN,
                    'min' => 1,
                    'step' => '1',
                    'type' => 'number',
                    'default' => NS\DEFAULT_IS_STATUS_IDLE_MIN,
                    'sanitize_callback' => 'absint'
                ),
            ),
            $this->plugin_name . '_advanced' => array(
                array(
                    'name' => 'is_geo_tracker_enabled',
                    'label' => esc_html__('Geo Tracker', 'faulh') . "<br>" . esc_html__('(Not Recommended)', 'faulh'),
                    'desc' => esc_html__('Enable tracking of country and timezone.', 'faulh') . "<br>" . esc_html__('This functionality is dependent on a free third-party API service, hence not recommended.', 'faulh'),
                    'type' => 'checkbox',
                    'default' => FALSE,
                ),
            ),
        );

        return $settings_fields;
    }

    /**
     * Get all the basics settings.
     * @return mixed
     */
    private function get_basic_settings() {
        return get_option($this->plugin_name . "_basics");
    }

    /**
     * Get all the advanced settings.
     * @return mixed
     */
    private function get_advanced_settings() {
        return get_option($this->plugin_name . "_advanced");
    }

    /**
     * Get duration for online and idle statuses.
     * @return array
     */
    public function get_online_duration() {
        $settings = $this->get_basic_settings();
        $minute_online = !empty($settings['is_status_online']) ? absint($settings['is_status_online']) : NS\DEFAULT_IS_STATUS_ONLINE_MIN;
        $minute_idle = !empty($settings['is_status_idle']) ? absint($settings['is_status_idle']) : NS\DEFAULT_IS_STATUS_IDLE_MIN;
        return array('online' => $minute_online, 'idle' => $minute_idle);
    }

    /**
     * Check if the geo tracker setting is enabled.
     * @return bool
     */
    public function is_geo_tracker_enabled() {
        $options = $this->get_advanced_settings();
        return (!empty($options['is_geo_tracker_enabled']) && 'on' == $options['is_geo_tracker_enabled']);
    }

    /**
     * Render the admin settings page.
     */
    public function plugin_page() {
        echo '<div class="wrap">';
        \User_Login_History\Inc\Common\Helpers\Template::head(esc_html__('Settings', 'faulh'));
        $this->Settings_Api->show_navigation();
        $this->Settings_Api->show_forms();
        echo '</div>';
    }

}
