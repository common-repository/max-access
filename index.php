<?php
/**
 * Plugin Name:       Max Access - DEPRECATED (new plugin available)
 * Description:       DEPRECATED - Support for this plugin has MOVED. To get future updates please use the new Max Access plugin: https://wordpress.org/plugins/accessibility-toolbar/
 * Version:           2.0.0
 * Author:            Ability, Inc.
 * Author URI:        https://maxaccess.io/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.7
 * Requires PHP: 7.4
 */


if (!defined('ABSPATH')) {
    exit;
}

define('ma2_DIR', __DIR__);

define('ma2_VERSION', '2.0.0');

define('ma2_MODE', 'prod');

define('ma2_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

define('ma2_FILE_PATH', __FILE__);

define('ma2_NAMESPACE', __NAMESPACE__);

wp_register_script('max-access', '/wp-content/plugins/max-access/src/admin.js');
wp_localize_script('max-access', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
wp_enqueue_script('max-access', '/wp-content/plugins/max-access/src/admin.js');

wp_register_style('max-access-styles','/wp-content/plugins/max-access/src/style.css');
wp_enqueue_style('max-access-styles', '/wp-content/plugins/max-access/src/style.css');

// the vue code needs type=module.
//thx to //https://micksp.medium.com/integrating-vue-in-a-wordpress-plugin-135f875c9913 for this snippet
add_filter('script_loader_tag', function($tag, $handle, $src) {
    if ( 'max-access' !== $handle ) {
        return $tag;
    }
    // change the script tag by adding type="module" and return it.
    $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    return $tag;
} , 10, 3);

add_action( 'init', 'ma2_plugin__activated');

function ma2_plugin__activated() {
    $old_license = get_option('ll_at_license');
    if ( isset($old_license) && !empty($old_license) ) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://accounts.onlineada.com' . '/api/ada-toolbar-check/'. $old_license);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $return_value = curl_exec($ch);
        curl_close($ch);
        $return_value = json_decode($return_value);

        if ( isset($return_value->license) && !empty($return_value->license) ) {
            delete_option('ll_at_license');
            update_option('toolbar_license_key', $return_value->license->key);
        } else {
            delete_option('ll_at_license');
            update_option('toolbar_license_key', $old_license);
        }
    }
}

add_action('admin_menu', 'register_ma2_plugin_menu', 9);

function register_ma2_plugin_menu(){
    add_menu_page(
        'Max Access',
        'Max Access',
        'manage_options',
        '/max-access-plugin-menu',
        'display_ma2_plugin_menu',
        'dashicons-admin-generic',
        ''
    );
};

function display_ma2_plugin_menu(){
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo '<div id="oada_accessibility_toolbar_admin"></div>';
}

add_action('wp_ajax_get_licenses', 'get_licenses2');

function get_licenses2(){
    $ch = curl_init();

    if(!defined('OADA_ACCOUNTS_URL')) {
        define('OADA_ACCOUNTS_URL', 'https://accounts.onlineada.com');
    }

    $key = $_GET['license_key'] == 'init' ? get_option('toolbar_license_key') : $_GET['license_key'];

    curl_setopt($ch, CURLOPT_URL, OADA_ACCOUNTS_URL . '/api/ada-toolbar-check/'. $key);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $return_value = curl_exec($ch);
    curl_close($ch);

    $return_value = json_decode($return_value, true);

    //we returned an upgraded license
    if($return_value['key'] !== $return_value['license']['key']) {
        update_option('toolbar_license_key', $return_value['license']['key']);
    } else if ( $_GET['license_key'] != 'init' ) {
        update_option('toolbar_license_key', $_GET['license_key']);
    }

    $return_value = json_encode($return_value);
    echo $return_value;
    wp_die();
}

add_action( 'wp_loaded', 'inject_toolbar_scripts2');

function inject_toolbar_scripts2()
{

    $key = get_option('toolbar_license_key');

    $script = '<script id="oada_ma2_toolbar_script">var oada_ma_license_key="'. $key .'";var oada_ma_license_url="https://api.maxaccess.io/scripts/toolbar/'. $key .'";(function(s,o,g){a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.src=g;a.setAttribute("async","");a.setAttribute("type","text/javascript");a.setAttribute("crossorigin","anonymous");m.parentNode.insertBefore(a,m)})(document,"script",oada_ma_license_url+oada_ma_license_key);</script>';

    wp_register_script('ma2_toolbar_script', '');
    wp_enqueue_script('ma2_toolbar_script', '', null, NULL, false);
    wp_add_inline_script('ma2_toolbar_script', $script);
}