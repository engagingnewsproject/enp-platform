<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Mask_Login extends Setting {
	/**
	 * Option name
	 * @var string
	 */
	public $table = 'wd_masking_login_settings';
	/**
	 * The URL we use to replace tradition wp-admin
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_mask_url
	 */
	public $mask_url = '';

	/**
	 * Enable to redirect if user visit to tradition wp-admin or wp-login.php
	 *
	 * @var string
	 * @defender_property
	 */
	public $redirect_traffic = 'off';

	/**
	 * The URL to redirect
	 *
	 * @var string
	 * @defender_property
	 * @sanitize_text_field
	 */
	public $redirect_traffic_url = '';

	/**
	 * The page/post id to redirect
	 * @var int
	 * @defender_property
	 */
	public $redirect_traffic_page_id;

	/**
	 * Main switch of this function
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * The ticket so anyone with this can bypass the mask login, we'll need this in certains cases
	 * @var array
	 */
	public $express_tickets = [];

	/**
	 * Backward compatibility with older version
	 * @var array
	 */
	protected $mapping = [
		'express_tickets' => 'otps',
	];

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = [
			'enabled'                  => __( 'Mask Login Area', 'wpdef' ),
			'mask_url'                 => __( 'Masking URL', 'wpdef' ),
			'redirect_traffic'         => __( 'Redirect traffic', 'wpdef' ),
			'redirect_traffic_url'     => __( 'Redirection URL', 'wpdef' ),
			'redirect_traffic_page_id' => __( 'Select the page or post your default login URLs will redirect to', 'wpdef' ),
		];

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		return apply_filters( 'wd_mask_login_enable', $this->enabled && '' !== $this->mask_url );
	}

	/**
	 * @param string|null $domain
	 *
	 * @return string
	 */
	public function get_new_login_url( $domain = null ) {
		if ( empty( $this->mask_url ) ) {
			return '';
		}

		if ( null === $domain ) {
			$domain = site_url();
		}

		return $domain . '/' . ltrim( $this->mask_url, '/' );
	}

	/**
	 * @return string
	 */
	public function get_redirect_url() {
		if ( empty( $this->redirect_traffic_url ) ) {
			return '';
		}

		if ( 'url' === $this->is_url_or_slug() ) {
			return $this->redirect_traffic_url;
		} else {
			return untrailingslashit( get_home_url( get_current_blog_id() ) ) . '/' . ltrim( $this->redirect_traffic_url,
					'/' );
		}
	}

	/**
	 * Check the entered string is URL or slug of the internal link
	 *
	 * @return string
	 */
	public function is_url_or_slug() {
		$regex = '/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[\d]{1,5})?(\/.*)?$/i';

		if ( preg_match( $regex, $this->redirect_traffic_url ) ) {
			return 'url';
		} else {
			return 'slug';
		}
	}

	/**
	 * Custom validate
	 * 1. No forbidden URL
	 * 2. No conflict with current post/page slug
	 * 3. Mask URl should not same with redirect URL
	 * @return bool|void
	 */
	public function after_validate() {
		if ( empty( $this->mask_url ) ) {
			return;
		}
		$forbidden = [
			'login',
			'wp-admin',
			'admin',
			'dashboard',
			'wp-login',
			'wp-login.php',
			// for change '.' to '-'
			'wp-login-php',
		];
		//remove the double slash
		$this->mask_url = ltrim( $this->mask_url, '/\\' );

		if ( in_array( $this->mask_url, $forbidden, true ) ) {
			$this->errors[] = __( 'The slug you have provided cannot be used for masking your login area. Please try a new one.', 'wpdef' );

			return false;
		}
		$exits = get_page_by_path( $this->mask_url, OBJECT, [ 'post', 'page' ] );
		if ( is_object( $exits ) ) {
			$this->errors[] = __( 'A page already exists at this URL, please pick a unique page for your new login area.', 'wpdef' );

			return false;
		}

		if ( $this->mask_url === $this->redirect_traffic_url ) {
			$this->errors[] = __( 'Redirect URL must different from Login URL', 'wpdef' );

			return false;
		}
	}
}