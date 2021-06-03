<?php
/*
Plugin Name: ImageRecycle pdf & image compression
Plugin URI: https://www.imagerecycle.com/cms/wordpress
Description: ImageRecycle is an automatic image & PDF optimizer that save up to 80% of the media weight without loosing quality. Speed up your website, keep visitors on board!
Author: ImageRecycle
Text Domain: wpio
Version: 3.1.9
Author URI: https://www.imagerecycle.com
Licence : GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
Copyright : Copyright (C) 2014 Imagerecycle (https://www.imagerecycle.com). All rights reserved.
*/

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

if (!defined('WPIO_IMAGERECYCLE')) {
    define('WPIO_IMAGERECYCLE', plugin_dir_path(__FILE__));
}

if (!defined('WPIO_IMAGERECYCLE_URL')) {
    define('WPIO_IMAGERECYCLE_URL', plugin_dir_url(__FILE__));
}

if (!defined('WPIO_IMAGERECYCLE_VERSION')) {
    define('WPIO_IMAGERECYCLE_VERSION', '3.1.9');
}

require_once(WPIO_IMAGERECYCLE . 'class/class-image-otimizer.php');
register_activation_hook(__FILE__, array('wpImageRecycle', 'install'));
register_uninstall_hook(__FILE__, array('wpImageRecycle', 'uninstall'));

add_action('plugins_loaded', array('wpImageRecycle', 'update_db_check'));

new wpImageRecycle();

/**
 * Callback function for 'wp_ajax__ajax_fetch_custom_list' action hook.
 *
 * Loads the Custom List Table Class and calls ajax_response method
 */
function _ajax_fetch_wpio_callback()
{
    $wpio_table = new WPIOTable();
    $wpio_table->ajax_response();
}

add_action('wp_ajax__ajax_fetch_wpio', '_ajax_fetch_wpio_callback');

// Include irfeedback helpers
require_once('irfeedback'. DIRECTORY_SEPARATOR . 'irfeedback.php');
call_user_func(
    '\ImageRecycle\ImageRecycle\Irfeedback\Irfeedback::init',
    __FILE__,
    'wpio',
    'imagerecycle-pdf-image-compression',
    'ImageRecycle pdf & image compression',
    'wpio'
);