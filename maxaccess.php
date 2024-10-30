<?php
/**
 * Plugin Name:       Max Access
 * Plugin URI: https://maxaccess.io/
 * Description:       This plugin helps expedite the Max Access installation process on wordpress by effortlessly connecting your Online ADA account to your website. 
 * Version:           1.0.9
 * Requires at least: 4.6
 * Requires PHP:      5.6
 * Author:            Online ADA
 * Author URI:        https://onlineada.com
 * License:           License: GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MAX_ACCESS_DIR', __DIR__);

require MAX_ACCESS_DIR . '/vendor/autoload.php';

$max_access = \MaxAccess\Plugin::getInstance();

register_activation_hook(__FILE__, 'max_access_plugin__activated');

function max_access_plugin__activated() {
}

?>