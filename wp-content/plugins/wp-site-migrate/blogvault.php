<?php
/*
Plugin Name: WP Engine Automated Migration
Plugin URI: http://wpengine.com
Description: The easiest way to migrate your site to WP Engine
Author: WP Engine
Author URI: http://blogvault.net/
Version: 1.43
Network: True
 */

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Global response array */
global $bvVersion;
global $blogvault;
global $bvDynamicEvents;
global $bvmanager;
$bvVersion = '1.43';

if (is_admin())
	require_once dirname( __FILE__ ) . '/admin.php';

if (!class_exists('BVHttpClient')) {
	require_once dirname( __FILE__ ) . '/bv_http_client.php';
}

if (!class_exists('BlogVault')) {
	require_once dirname( __FILE__ ) . '/bv_class.php';
	$blogvault = BlogVault::getInstance();
}

if (!class_exists('BVDynamicBackup')) {
	require_once dirname( __FILE__ ) . '/bv_dynamic_backup.php';
}

if (!class_exists('BVSecurity')) {
	require_once dirname( __FILE__ ) . '/bv_security.php';
	$bvsecurity = BVSecurity::init();
}

add_action('bvdailyping_daily_event', array($blogvault, 'dailyping'));
if ( !function_exists('bvActivateHandler') ) :
	function bvActivateHandler() {
		global $blogvault;
		if (!wp_next_scheduled('bvdailyping_daily_event')) {
			wp_schedule_event(time(), 'daily', 'bvdailyping_daily_event');
		}
		if (!isset($_REQUEST['blogvaultkey'])) {
			##BVKEYSLOCATE##
		}
		$blogvault->updateOption('bvActivateTime', time());
		if ($blogvault->getOption('bvPublic') !== false) {
			$blogvault->updateOption('bvLastSendTime', time());
			$blogvault->updateOption('bvLastRecvTime', 0);
			$blogvault->activate();
		} else {
			$rand_secret = $blogvault->randString(32);
			$blogvault->updateOption('bvSecretKey', $rand_secret);
			$blogvault->updateOption('bvActivateRedirect', 'yes');
		}
	}
	register_activation_hook(__FILE__, 'bvActivateHandler');
endif;

if ( !function_exists('bvDeactivateHandler') ) :
	function bvDeactivateHandler() {
		global $blogvault;
		wp_clear_scheduled_hook('bvdailyping_daily_event');
		$body = array();
		$body['wpurl'] = urlencode($blogvault->wpurl());
		$body['url2'] = urlencode(get_bloginfo('wpurl'));
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$resp = $clt->post($blogvault->getUrl("deactivate"), array(), $body);
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}
	register_deactivation_hook(__FILE__, 'bvDeactivateHandler');
endif;


if ((array_key_exists('apipage', $_REQUEST)) && stristr($_REQUEST['apipage'], 'blogvault')) {
	
	if ((array_key_exists('manage', $_REQUEST)) && !class_exists('BVManageSite')) {
		require_once dirname( __FILE__ ) . '/bv_manage_site.php';
		$bvmanager = BVManageSite::init();
	}
	if (array_key_exists('afterload', $_REQUEST)) {
		add_action('wp_loaded', array($blogvault, 'processApiRequest'));
	} else {
		$blogvault->processApiRequest();
	}
} else {
	# Do not load dynamic sync for callback requests
	$isdynsyncactive = $blogvault->getOption('bvDynSyncActive');
	if ($isdynsyncactive == 'yes') {
		BVDynamicBackup::init();
	}
}