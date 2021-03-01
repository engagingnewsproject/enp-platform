<?php

if ( !defined('NF_SERVER_URL') )
 	define('NF_SERVER_URL', 'https://my.ninjaforms.com');

// Library
require_once( plugin_dir_path( __FILE__ ) . 'lib/keygen.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/webhooks/router.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/webhooks/response.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/webhooks/controller.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/wordpress/plugin.php' );

// Project includes
require_once( plugin_dir_path( __FILE__ ) . 'includes/plugin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/service.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/app/webhooks/webhook-example.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/app/webhooks/webhook-sync.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/app/webhooks/webhook-install.php' );
