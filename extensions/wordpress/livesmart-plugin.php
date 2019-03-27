<?php

/*
  Plugin Name: jsLive Video
  Plugin URI: https://shameem.me
  Description: Custom plugin to use jsLive with WordPress.
  Version: 0.0.1
  Author: Shameem Reza
  Author URI: https://shameem.me
 */

function html_jslive_code($names, $avatar, $roomId) {
    $jslive_css = (get_option('jslive_css') != '') ? get_option('jslive_css') : '';
    $message = (get_option('jslive_front_message') != '') ? get_option('jslive_front_message') : 'Start Video Chat';
    $names = $names ? $names : get_option('jslive_names');
    $avatar = $avatar ? $avatar : get_option('jslive_avatar');
    $jslive_server_url = (get_option('jslive_server_url') != '') ? get_option('jslive_server_url') : '';
    echo '<div id="nd-widget-container" class="nd-widget-container"></div> 
	<script id="shameemreza-embed-script" data-room-id="'.$roomId.'" data-names="'.$names.'" data-avatar="'.$avatar.'" data-message="'.$message.'" data-button-css="'.$jslive_css.'" data-source_path="' . $jslive_server_url . '" src="' . $jslive_server_url . 'js/widget.js" async></script>';
}

function jl_shortcode($atts = [], $content = null, $tag = '') {
    $names = isset($atts['names']) ? $atts['names'] : '';
    $avatar = isset($atts['avatar']) ? $atts['avatar'] : '';
    $roomId = isset($atts['roomId']) ? $atts['roomId'] : '';
    ob_start();
    html_jslive_code($names, $avatar, $roomId);

    return ob_get_clean();
}

add_shortcode('jslive_widget', 'jl_shortcode');

add_action('admin_menu', 'jslive_plugin_settings');

add_action('jslive_widget', 'html_jslive_code');

function jslive_plugin_settings() {
    add_menu_page('jsLive Settings', 'jsLive Settings', 'administrator', 'fwds_settings', 'jslive_display_settings');
}

function jslive_display_settings() {

    $jslive_server_url = (get_option('jslive_server_url') != '') ? get_option('jslive_server_url') : '';
    $jslive_css = (get_option('jslive_css') != '') ? get_option('jslive_css') : '';
    $message = (get_option('jslive_front_message') != '') ? get_option('jslive_front_message') : '';
    $names = (get_option('jslive_names') != '') ? get_option('jslive_names') : '';
    $avatar = (get_option('jslive_avatar') != '') ? get_option('jslive_avatar') : '';
    $html = '<div class="wrap">

            <form method="post" name="options" action="options.php">

            <h2>Select Your Settings</h2>' . wp_nonce_field('update-options') . '
            <table width="300" cellpadding="2" class="form-table">
                <tr>
                    <td align="left" scope="row">
                    <label>Server URL</label>
                    </td> 
                    <td><input type="text" style="width: 400px;" name="jslive_server_url" 
                    value="' . $jslive_server_url . '" /></td>
                </tr>      
                <tr>
                    <td align="left" scope="row">
                    <label>Button CSS</label>
                    </td> 
                    <td><input type="text" style="width: 400px;" name="jslive_css" 
                    value="' . $jslive_css . '" /></td>
                </tr>
                <tr>
                    <td align="left" scope="row">
                    <label>Button Message</label>
                    </td> 
                    <td><input type="text" style="width: 400px;" name="jslive_front_message" 
                    value="' . $message . '" /></td>
                </tr>
                <tr>
                    <td align="left" scope="row">
                    <label>Agent Name</label>
                    </td> 
                    <td><input type="text" style="width: 400px;" name="jslive_names" 
                    value="' . $names . '" /></td>
                </tr>
                <tr>
                    <td align="left" scope="row">
                    <label>Agent Avatar</label>
                    </td> 
                    <td><input type="text" style="width: 400px;" name="jslive_avatar" 
                    value="' . $avatar . '" /></td>
                </tr>

            </table>
            <p class="submit">
                <input type="hidden" name="action" value="update" />  
                <input type="hidden" name="page_options" value="jslive_names,jslive_avatar,jslive_server_url,jslive_front_message,jslive_css" /> 
                <input type="submit" name="Submit" value="Update" />
            </p>
            </form>

        </div>';
    echo $html;
}

?>