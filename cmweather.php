<?php
/*
 * Plugin Name:       CM Weather
 * Description:       Weather widget for a specified place using WeatherAPI.com data.
 * Version:           1.0
 * Author:            Cameron Main
 * Author URI:        https://www.cameronmain.com/
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

 require_once plugin_dir_path(__FILE__) . 'includes/cmw-functions.php';

// === ADMIN SETTINGS PAGE ===
add_action('admin_menu', 'cmw_add_admin_menu');
function cmw_add_admin_menu() {
    add_options_page(
        'CM Weather Settings',
        'CM Weather',
        'manage_options',
        'cm-weather',
        'cmw_settings_page'
    );
}

add_action('admin_init', 'cmw_settings_init');
function cmw_settings_init() {
    register_setting('cmw_settings_group', 'cmw_location');
    register_setting('cmw_settings_group', 'cmw_api_key');
    register_setting('cmw_settings_group', 'cmw_temperature_unit');
    add_settings_section('cmw_section', 'Weather Settings', null, 'cm-weather');
    add_settings_field(
        'cmw_location',
        'Location (City, Town, or Postcode)',
        'cmw_location_field_render',
        'cm-weather',
        'cmw_section'
    );
    add_settings_field(
        'cmw_api_key',
        'WeatherAPI.com API Key',
        'cmw_api_key_field_render',
        'cm-weather',
        'cmw_section'
    );
    add_settings_field(
        'cmw_temperature_unit',
        'Temperature Unit',
        'cmw_temperature_unit_field_render',
        'cm-weather',
        'cmw_section'
    );
}

function cmw_location_field_render() {
    $value = get_option('cmw_location', 'Miami');
    echo '<input type="text" name="cmw_location" value="' . esc_attr($value) . '" />';
}

function cmw_api_key_field_render() {
    $value = get_option('cmw_api_key', '');
    echo '<input type="password" name="cmw_api_key" value="' . esc_attr($value) . '" autocomplete="off" />';
}

function cmw_temperature_unit_field_render() {
    $value = get_option('cmw_temperature_unit', 'C');
    ?>
    <select name="cmw_temperature_unit">
        <option value="C" <?php selected($value, 'C'); ?>>Celsius (°C)</option>
        <option value="F" <?php selected($value, 'F'); ?>>Fahrenheit (°F)</option>
    </select>
    <?php
}

function cmw_settings_page() {
    ?>
    <div class="wrap">
        <h1>CM Weather Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cmw_settings_group');
            do_settings_sections('cm-weather');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


 function cmw_init_plugin() {
     new CM_Weather();
 }
 add_action('plugins_loaded', 'cmw_init_plugin');
 
 add_action('wp_enqueue_scripts', 'cmw_init_styles');
 function cmw_init_styles() {
     wp_register_style( 'cmw-style', plugin_dir_url(__FILE__) .'assets/css/cmw-style.css' );
     wp_enqueue_style( 'cmw-style' );
 }
