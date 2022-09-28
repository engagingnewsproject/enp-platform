<?php
declare( strict_types=1 );

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Two_Fa extends Setting {
	public const CUSTOM_GRAPHIC_TYPE_UPLOAD = 'upload', CUSTOM_GRAPHIC_TYPE_LINK = 'link', CUSTOM_GRAPHIC_TYPE_NO = 'no';

	public $table = 'wd_2auth_settings';

	/**
	 * Feature status.
	 *
	 * @defender_property
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * @defender_property
	 * @var bool
	 */
	public $lost_phone = true;

	/**
	 * @defender_property
	 * @var bool
	 */
	public $force_auth = false;

	/**
	 * @var string
	 * @defender_property
	 */
	public $force_auth_mess = '';

	/**
	 * @var array
	 * @defender_property
	 */
	public $user_roles = [];

	/**
	 * @var array
	 * @defender_property
	 */
	public $force_auth_roles = [];

	/**
	 * Enable/disable custom graphic feature.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $custom_graphic = false;

	/**
	 * Type of graphic. For example; uploading image, using image link or none.
	 *
	 * @var string
	 * @defender_property
	 */
	public $custom_graphic_type = '';

	/**
	 * Upload image for custom graphic.
	 *
	 * @var string
	 * @defender_property
	 */
	public $custom_graphic_url = '';

	/**
	 * Use image link for custom graphic.
	 *
	 * @var string
	 * @defender_property
	 */
	public $custom_graphic_link = '';

	/**
	 * @var array
	 */
	public $is_conflict = [];

	/**
	 * @var string
	 * @defender_property
	 */
	public $email_subject = '';

	/**
	 * @var string
	 * @defender_property
	 */
	public $email_sender = '';

	/**
	 * @defender_property
	 * @var string
	 */
	public $email_body = '';

	/**
	 * @var string
	 * @defender_property
	 */
	public $app_title = '';

	/**
	 * @var string
	 */
	public $app_text = '';

	/**
	 * @var bool
	 * @defender_property
	 */
	public $detect_woo = false;

	/**
	 * @return array
	 */
	public function get_default_values(): array {
		return [
			'custom_graphic_type' => self::CUSTOM_GRAPHIC_TYPE_UPLOAD,
			'custom_graphic_url' => defender_asset_url( '/assets/img/2factor-disabled.svg' ),
			'custom_graphic_link' => '',
			'email_subject' => __( 'Your OTP code', 'wpdef' ),
			'email_sender' => 'admin',
			'email_body' => 'Hi {{display_name}},

Your temporary password is {{passcode}}. To finish logging in, copy and paste the temporary password into the Password field on the login screen.',
			'app_title' => '',
			'message' => __( 'You are required to setup two-factor authentication to use this site.', 'wpdef' ),
		];
	}

	/**
	 * @return void
	 */
	protected function before_load(): void {
		// Default we will load all rules.
		$default_values = $this->get_default_values();
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . '/wp-admin/includes/user.php';
		}
		$this->user_roles = array_keys( get_editable_roles() );
		// Define some other defaults.
		$this->custom_graphic_type = $default_values['custom_graphic_type'];
		$this->custom_graphic_url = $default_values['custom_graphic_url'];
		$this->custom_graphic_link = $default_values['custom_graphic_link'];
		$this->email_subject = $default_values['email_subject'];
		$this->email_sender = $default_values['email_sender'];
		$this->email_body = $default_values['email_body'];
		$this->app_title = empty( $default_values['app_title'] )
			? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			: $default_values['app_title'];
		$this->force_auth_mess = $default_values['message'];
		$this->app_text = __( 'Open your authentication app and type the 6 digit passcode to log in to your account.', 'wpdef' );
	}

	/**
	 * @return void
	 */
	protected function after_load(): void {
		$this->user_roles = array_values( $this->user_roles );
	}

	/**
	 * @param string $plugin
	 *
	 * @return bool|int
	 */
	public function is_conflict( $plugin ) {
		if ( in_array( $plugin, $this->is_conflict, true ) ) {
			return true;
		} elseif ( in_array( '!' . $plugin, $this->is_conflict, true ) ) {
			return false;
		}

		return 0;
	}

	/**
	 * @param string $plugin
	 *
	 * @return void
	 */
	public function mark_as_conflict( $plugin ): void {
		if ( ! in_array( $plugin, $this->is_conflict, true ) ) {
			$this->is_conflict[] = $plugin;
			$this->save();
		}
	}

	/**
	 * @param string $plugin
	 *
	 * @return void
	 */
	public function mark_as_un_conflict( $plugin ): void {
		$i = array_search( $plugin, $this->is_conflict, true );

		if ( $i !== false ) {
			unset( $this->is_conflict[ $i ] );
		}
		if ( ! in_array( '!' . $plugin, $this->is_conflict, true ) ) {
			$this->is_conflict [] = '!' . $plugin;
		}
		$this->save();
	}

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return [
			'enabled' => __( 'Mask Login Area', 'wpdef' ),
			'user_roles' => __( 'Enabled user roles', 'wpdef' ),
			'force_auth_roles' => __( 'Force users to log in with two-factor authentication', 'wpdef' ),
			'lost_phone' => __( 'Allow lost phone recovery option', 'wpdef' ),
			'email_subject' => __( 'Subject', 'wpdef' ),
			'email_body' => __( 'Body', 'wpdef' ),
			'email_sender' => __( 'Sender', 'wpdef' ),
			'force_auth' => __( 'Force 2FA on user roles', 'wpdef' ),
			'force_auth_mess' => __( 'Force 2FA login warning message', 'wpdef' ),
			'custom_graphic' => __( 'Use custom login branding graphic', 'wpdef' ),
			'custom_graphic_url' => __( 'Custom Graphic', 'wpdef' ),
			'app_title' => __( 'App Title', 'wpdef' ),
			'detect_woo' => __( 'WooCommerce', 'wpdef' ),
		];
	}
}
