<?php
/**
 * Handles 2FA settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for 2FA settings.
 */
class Two_Fa extends Setting {

	public const CUSTOM_GRAPHIC_TYPE_UPLOAD = 'upload', CUSTOM_GRAPHIC_TYPE_LINK = 'link', CUSTOM_GRAPHIC_TYPE_NO = 'no';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_2auth_settings';

	/**
	 * Feature status.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Allow lost phone recovery option.
	 *
	 * @defender_property
	 * @var bool
	 */
	public $lost_phone = true;

	/**
	 * Is 'Force Authentication' checked?
	 *
	 * @defender_property
	 * @var bool
	 */
	public $force_auth = false;

	/**
	 * Force authentication message.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $force_auth_mess = '';

	/**
	 * Enable/disable 2FA for user roles.
	 *
	 * @var array
	 * @defender_property
	 */
	public $user_roles = array();

	/**
	 * Allowed user roles for 'Force Authentication'.
	 *
	 * @var array
	 * @defender_property
	 */
	public $force_auth_roles = array();

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
	 * List of plugins that conflict with 2FA.
	 *
	 * @var array
	 */
	public $is_conflict = array();

	/**
	 * Email subject.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_text_field
	 */
	public $email_subject = '';

	/**
	 * Email sender.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_text_field
	 */
	public $email_sender = '';

	/**
	 * Email body.
	 *
	 * @defender_property
	 * @var string
	 * @sanitize sanitize_textarea_field
	 */
	public $email_body = '';

	/**
	 * App title.
	 *
	 * @var string
	 * @defender_property
	 * @sanitize sanitize_textarea_field
	 */
	public $app_title = '';

	/**
	 * It's not for DB, no need 'defender_property' annotation. Just text view on different pages.
	 *
	 * @var string
	 */
	public $app_text = '';

	/**
	 * Detect Woocommerce.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $detect_woo = false;

	/**
	 * Get default values.
	 *
	 * @return array
	 */
	public function get_default_values(): array {
		return array(
			'custom_graphic_type' => self::CUSTOM_GRAPHIC_TYPE_UPLOAD,
			'custom_graphic_url'  => defender_asset_url( '/assets/img/2factor-disabled.svg' ),
			'custom_graphic_link' => '',
			'email_subject'       => __( 'Your OTP code', 'wpdef' ),
			'email_sender'        => 'admin',
			'email_body'          => 'Hi {{display_name}},

Your temporary password is {{passcode}}
To complete your login, copy and paste the temporary password into the Password field on the login screen.',
			'app_title'           => '',
			'message'             => esc_html__(
				'You are required to setup two-factor authentication to use this site.',
				'wpdef'
			),
		);
	}

	/**
	 * Load default values.
	 *
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
		$this->custom_graphic_url  = $default_values['custom_graphic_url'];
		$this->custom_graphic_link = $default_values['custom_graphic_link'];
		$this->email_subject       = $default_values['email_subject'];
		$this->email_sender        = $default_values['email_sender'];
		$this->email_body          = $default_values['email_body'];
		$this->app_title           = empty( $default_values['app_title'] )
			? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			: $default_values['app_title'];
		$this->force_auth_mess     = $default_values['message'];
		$this->app_text            = esc_html__(
			'Open your authentication app and type the 6 digit passcode to log in to your account.',
			'wpdef'
		);
	}

	/**
	 * After loading, reindexes the user roles array.
	 *
	 * @return void
	 */
	protected function after_load(): void {
		$this->user_roles = array_values( $this->user_roles );
	}

	/**
	 * Check if a plugin is in conflict with the current instance.
	 *
	 * @param  string $plugin  The plugin to check for conflict.
	 *
	 * @return int Returns 0 if the plugin is not in conflict, 1 if it is in conflict, and 0 if it is not in conflict
	 *     but has been marked as not conflicting.
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
	 * Marks a plugin as conflicting with the current instance.
	 *
	 * @param  string $plugin  The plugin to mark as conflicting.
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
	 * Marks a plugin as not conflicting with the current instance.
	 *
	 * @param  string $plugin  The plugin to mark as not conflicting.
	 *
	 * @return void
	 */
	public function mark_as_un_conflict( $plugin ): void {
		$i = array_search( $plugin, $this->is_conflict, true );

		if ( false !== $i ) {
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
		return array(
			'enabled'             => self::get_module_name(),
			'user_roles'          => esc_html__( 'Enabled user roles', 'wpdef' ),
			'lost_phone'          => esc_html__( 'Allow lost phone recovery option', 'wpdef' ),
			'email_subject'       => esc_html__( 'Subject', 'wpdef' ),
			'email_body'          => esc_html__( 'Body', 'wpdef' ),
			'email_sender'        => esc_html__( 'Sender', 'wpdef' ),
			'force_auth'          => esc_html__( 'Force 2FA on user roles', 'wpdef' ),
			'force_auth_roles'    => esc_html__( 'Force users to log in with two-factor authentication', 'wpdef' ),
			'force_auth_mess'     => esc_html__( 'Force 2FA login warning message', 'wpdef' ),
			'custom_graphic'      => esc_html__( 'Custom Graphic', 'wpdef' ),
			'custom_graphic_type' => esc_html__( 'Enable custom graphics above login fields', 'wpdef' ),
			'custom_graphic_url'  => esc_html__( 'Upload Graphic', 'wpdef' ),
			'custom_graphic_link' => esc_html__( 'Link Graphic', 'wpdef' ),
			'app_title'           => esc_html__( 'App Title', 'wpdef' ),
			'detect_woo'          => esc_html__( 'WooCommerce', 'wpdef' ),
		);
	}

	/**
	 * Checks if the 2FA feature is active.
	 *
	 * @return bool Returns true if the 2FA feature is active, false otherwise.
	 * @since 3.12.0
	 */
	public function is_active(): bool {
		return (bool) apply_filters( 'wd_2fa_enable', $this->enabled );
	}

	/**
	 * Retrieves the module name for Two-Factor Authentication.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Two-Factor Authentication', 'wpdef' );
	}
}