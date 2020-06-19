<?php

/**
 * Ga_Sharethis class
 *
 * Preparing request and parsing response from Sharethis Platform Api
 *
 * @author wle@adips.com
 * @version 1.0
 */

class Ga_Sharethis {

	public static function get_body( $data ) {
		$body = $data->getBody();
		return json_decode( $body );
	}

	/**
	 * Create sharethis options
	 */
	public static function create_sharethis_options( $api_client ) {
		$data = array();
		$parsed_url = parse_url( get_option( 'siteurl' ) );
		$domain				 = $parsed_url['host'] . ( !empty( $parsed_url['path'] ) ? $parsed_url['path'] : '' );
		$query_params		 = array(
			'domain' => $domain,
			'is_wordpress' => true,
			'onboarding_product' => 'ga',
			);
		$response			 = $api_client->call( 'ga_api_create_sharethis_property', array(
			$query_params
		) );
		$sharethis_options	 = self::get_sharethis_options( $response );
		if ( !empty( $sharethis_options[ 'id' ] ) ) {
			add_option( Ga_Admin::GA_SHARETHIS_PROPERTY_ID, $sharethis_options[ 'id' ] );
		}
		if ( !empty( $sharethis_options[ 'secret' ] ) ) {
			add_option( Ga_Admin::GA_SHARETHIS_PROPERTY_SECRET, $sharethis_options[ 'secret' ] );
		}

		return $data;
	}

	public static function get_sharethis_options( $response ) {
		$body	 = self::get_body( $response );
		$options = array();
		if ( !empty( $body ) ) {
			foreach ( $body as $key => $value ) {
				if ( $key == '_id' ) {
					$options[ 'id' ] = $value;
				} else if ( $key == 'secret' ) {
					$options[ 'secret' ] = $value;
				} else if ( $key == 'error' ) {
					$options[ 'error' ] = $value;
				}
			}
		} else {
			$options[ 'error' ] = 'error';
		}
		return $options;
	}

	public static function sharethis_installation_verification( $api_client ) {
		if ( Ga_Helper::should_verify_sharethis_installation() ) {
			$query_params	 = array(
				'id'	 => get_option( Ga_Admin::GA_SHARETHIS_PROPERTY_ID ),
				'secret' => get_option( Ga_Admin::GA_SHARETHIS_PROPERTY_SECRET )
			);
			$response		 = $api_client->call( 'ga_api_sharethis_installation_verification', array(
				$query_params
			) );
			$result			 = self::get_verification_result( $response );
			if ( !empty( $result ) ) {
				add_option( Ga_Admin::GA_SHARETHIS_VERIFICATION_RESULT, true );
			}
		}
	}

	public static function get_verification_result( $response ) {
		$body = self::get_body( $response );
		if ( !empty( $body->{"status"} ) ) {
			return true;
		}
		return false;
	}

	public static function get_alerts( $response ) {
		$body = self::get_body( $response );
		if ( !empty( $body ) ) {
			if ( !empty( $body[ 'error' ] ) ) {
				return (object) array( 'error' => self::GA_SHARETHIS_ALERTS_ERROR );
			}

			return $body;
		} else {
			return array();
		}
	}

}
