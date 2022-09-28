<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

require_once __DIR__ . '/logger.php';

class UserRequestIdHelper {

	const MAX_NO_OF_REQUEST_IDS  = 10;
	const WPE_LOGGED_REQUEST_IDS = 'WPE_LOGGED_REQUEST_IDS';

	public function __construct() {
	}

	public function update_request_id_user_meta( $user_email, $request_id ) {
		list( $request_id_array, $user) = $this->get_request_id_array_and_user_for_user_email( $user_email );

		if ( 0 === count( $request_id_array ) ) {
			return add_user_meta( $user->ID, self::WPE_LOGGED_REQUEST_IDS, array( $request_id ) );
		} else {
			$request_id_array = $this->add_item_to_array( $request_id_array[0], $request_id );
			return update_user_meta( $user->ID, self::WPE_LOGGED_REQUEST_IDS, $request_id_array );
		}
	}

	public function request_id_matches_logged_request_id_for_user( $user_email, $request_id ) {
		list( $request_id_array, $user) = $this->get_request_id_array_and_user_for_user_email( $user_email );

		$index_to_delete  = -1;
		$return_value     = false;
		$request_id_array = 0 === count( $request_id_array ) ? $request_id_array : $request_id_array[0];

		foreach ( $request_id_array as $key => $val ) {
			if ( $val === $request_id ) {
				$index_to_delete = $key;
				$return_value    = true;
				break;
			}
		}
		if ( -1 !== $index_to_delete ) {
			unset( $request_id_array[ $index_to_delete ] );
			$request_id_array = array_values( $request_id_array );
			update_user_meta( $user->ID, self::WPE_LOGGED_REQUEST_IDS, $request_id_array );
		}
		return $return_value;
	}

	private function get_request_id_array_and_user_for_user_email( $user_email ) {
		$user = $this->get_wp_user( $user_email );
		wp_cache_delete( $user->ID, 'user_meta' );
		return array( get_user_meta( $user->ID, self::WPE_LOGGED_REQUEST_IDS, false ), $user );
	}

	private function add_item_to_array( $array, $item ) {
		if ( count( $array ) >= self::MAX_NO_OF_REQUEST_IDS ) {
			unset( $array[0] );
		}
		$array[] = $item;
		return array_values( $array );
	}

	private function get_wp_user( $user_email ) {
		$user = get_user_by( 'email', $user_email );
		$user = $user ? $user : new \WP_User();
		return $user;
	}
}
