<?php

class WPMUDEV_Dashboard_Test_Util {

	const WPMUDEV_API_KEY = '703cd83f1f7f929ebe5994207847e907ca1b3faa';

	public static $is_logged_in = false;

	public static function login() {
		WPMUDEV_Dashboard::$api->set_key( self::WPMUDEV_API_KEY );
		WPMUDEV_Dashboard::$api->hub_sync( false, true );
		self::$is_logged_in = true;
	}

	public static function logout() {
		if ( ! self::$is_logged_in ) {
			return;
		}
		WPMUDEV_Dashboard::$api->revoke_remote_access();
		WPMUDEV_Dashboard::$api->analytics_disable();
		WPMUDEV_Dashboard::$settings->reset();
		WPMUDEV_Dashboard::$api->set_key( '' );
		WPMUDEV_Dashboard::$api->hub_sync( false, true ); // force a sync so that site is removed from user's hub.
		self::$is_logged_in = false;
	}

	public static function open_json_data( $file_name ) {
		return file_get_contents( dirname( __FILE__ ) . '/data/' . $file_name );// phpcs:ignore WordPress.WP.AlternativeFunctions -- Internal usage for test purpose only
	}
}
