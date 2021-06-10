<?php
@set_time_limit(0);
if (defined('WPIO_PROCESSING_QUEUE')) {
    die();
}
define('WPIO_PROCESSING_QUEUE', true);

ob_end_clean();
header("Connection: close");
ignore_user_abort(true); // just to be safe
ob_start();
//echo('connected');
$size = ob_get_length();
header("Content-Length: $size");
ob_end_flush(); // Strange behaviour, will not work
flush(); // Unless both are called !

// Do processing here 
sleep(1);
if (!defined('ABSPATH')) {
    /** Set up WordPress environment */
    require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php');
}
require_once(dirname(__FILE__) . '/class/class-image-otimizer.php');
$wpir = new wpImageRecycle();
$wpir->wpio_auto_optimize();
die();
?>