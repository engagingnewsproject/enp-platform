<?php

namespace WP_Defender\Component\Two_Factor\Providers;

use WP_Defender\Component\Two_Factor\Two_Factor_Provider;

/**
 * Class Backup_Codes
 * @since 2.8.0
 * @package WP_Defender\Component\Two_Factor\Providers
 */
class Backup_Codes extends Two_Factor_Provider {
	static $slug = 'backup-codes';

	/**
	 * The number backup codes.
	 *
	 * @type int
	 */
	const BACKUP_CODE_COUNT = 5;

	/**
	 * @type int
	 */
	const BACKUP_CODE_SIZE = 8;

	/**
	 * @type string
	 */
	const BACKUP_CODE_CHARACTERS = '1234567890';

	/**
	 * The user meta key for backup codes.
	 *
	 * @type string
	 */
	const BACKUP_CODE_VALUES = 'wd_2fa_backup_codes';

	/**
	 * @type string
	 */
	const BACKUP_CODE_START = 'wd_2fa_backup_codes_is_activated';

	/**
	 * @type string
	 */
	protected $is_activated;

	/**
	 * @type string
	 */
	protected $description;

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wd_2fa_init_provider_' . self::$slug, array( &$this, 'init_provider' ) );
		add_action( 'wd_2fa_user_options_' . self::$slug, array( &$this, 'user_options' ) );
	}

	/**
	 * @param \WP_User $user
	 */
	public function init_provider( $user ) {
		$this->is_activated = get_user_meta( $user->ID,self::BACKUP_CODE_START, true );
		$this->description  = empty( $this->is_activated )
			? __( 'Generate non-expirable backup codes that can be used to log in once.', 'wpdef' )
			: __( 'Each backup code can only be used to log in once.', 'wpdef' );
	}

	/**
	 * Get the name of the provider.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Backup Codes', 'wpdef' );
	}

	/**
	 * @return string
	 */
	public function get_login_label() {
		return $this->get_label();
	}

	/**
	 * @return string
	 */
	public function get_user_label() {
		return __( 'Backup codes', 'wpdef' );
	}

	/**
	 * Get the desc of the provider.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Whether this 2FA provider is configured and codes are available for the user specified.
	 *
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public function is_available_for_user( $user ) {
		// Does this user have available codes?
		if ( 0 < self::get_unused_codes_for_user( $user ) ) {
			return true;
		}

		return false;
	}

	public function authentication_form() {
		?>
		<p class="wpdef-2fa-label"><?php echo $this->get_label(); ?></p>
		<p class="wpdef-2fa-text">
			<?php echo __( 'Enter one of your recovery codes to log in to your account.', 'wpdef' ); ?>
		</p>
		<input type="text" autofocus value="" autocomplete="off" name="backup-codes">
		<button class="button button-primary float-r" type="submit"><?php _e( "Authenticate", 'wpdef' ) ?></button>
		<?php
	}

	/**
	 * Return the number of unused codes for the specified user.
	 *
	 * @param \WP_User $user
	 *
	 * @return int
	 */
	public static function get_unused_codes_for_user( $user ) {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODE_VALUES, true );
		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			return count( $backup_codes );
		}

		return 0;
	}

	/**
	 * Generate a random string for an auth code.
	 *
	 * @return string
	 */
	private static function get_code() {
		$code = '';
		$chars = self::BACKUP_CODE_CHARACTERS;
		for ( $i = 0; $i < self::BACKUP_CODE_SIZE; $i++ ) {
			$code .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $code;
	}

	/**
	 * Show an admin notice when backup codes have run out.
	 */
	public function admin_notices() {
		$user = wp_get_current_user();
		if ( ! is_object( $user ) ) {
			return;
		}
		// Is User role from common list checked?
		if ( empty( array_intersect( $user->roles, $this->get_model()->user_roles ) ) ) {
			return;
		}
		// Return if the provider is not enabled.
		if ( ! in_array( self::$slug, $this->get_component()->get_enabled_providers_for_user( $user->ID ), true ) ) {
			return;
		}
		// Return if we are not out of codes.
		if ( $this->is_available_for_user( $user ) ) {
			return;
		}
		?>
		<div class="error">
			<p>
				<span>
					<?php
					echo wp_kses(
						sprintf(
						/* translators: %s: URL to regenerate code */
							__( 'You\'ve used all generated backup codes. Please <a href="%s">generate</a> new codes accordingly.', 'wpdef' ),
							esc_url( get_edit_user_link( $user->ID ) . '#wpdef-2fa-backup-codes' )
						),
						array( 'a' => array( 'href' => true ) )
					);
					?>
				<span>
			</p>
		</div>
		<?php
	}

	/**
	 * Display auth method.
	 *
	 * @param \WP_User $user
	 */
	public function user_options( $user ) {
		$cond = $this->get_component()->is_checked_enabled_provider_by_slug( $user, self::$slug )
			&& $this->is_available_for_user( $user );
		$attr = $cond ? '' : ' disabled';
		// Check whether codes were generated before or not.
		if ( empty( $this->is_activated ) ) {
			$button_text = __( 'Generate Backup Codes', 'wpdef' );
			$class       = 'hidden';
			$count       = 0;
			$show_notice = 'no';
		} else {
			$button_text = __( 'Get New Codes', 'wpdef' );
			$class       = $cond ? '' : 'hidden';
			$count       = self::get_unused_codes_for_user( $user );
			$show_notice = 0 === $count ? 'yes' : 'no';
		}
		$this->get_controller()->render_partial(
			'two-fa/providers/backup-codes',
			array(
				'number_of_codes' => self::display_number_of_codes( $count ),
				'url'             => $this->get_url( 'generate_backup_codes' ),
				'user'            => $user,
				'attr'            => $attr,
				'class'           => $class,
				'show_notice'     => $show_notice,
				'button_text'     => $button_text,
			)
		);
	}

	/**
	 * Generate backup codes.
	 *
	 * @param \WP_User $user
	 * @param array    $args
	 *
	 * @return array
	 */
	public static function generate_codes( $user, $args = '' ) {
		$codes        = array();
		$codes_hashed = array();
		// Check for arguments.
		if ( isset( $args['number'] ) ) {
			$num_codes = (int) $args['number'];
		} else {
			$num_codes = self::BACKUP_CODE_COUNT;
		}
		// Add a flag if it haven't existed yet.
		if ( empty( get_user_meta( $user->ID,self::BACKUP_CODE_START, true ) ) ) {
			update_user_meta( $user->ID,self::BACKUP_CODE_START, 1 );
		}
		// Append or replace (default).
		if ( isset( $args['method'] ) && 'append' === $args['method'] ) {
			$codes_hashed = (array) get_user_meta( $user->ID, self::BACKUP_CODE_VALUES, true );
		}

		for ( $i = 0; $i < $num_codes; $i++ ) {
			$code           = self::get_code();
			$codes_hashed[] = wp_hash_password( $code );
			$codes[]        = $code;
			unset( $code );
		}

		update_user_meta( $user->ID, self::BACKUP_CODE_VALUES, $codes_hashed );

		// Unhashed values.
		return $codes;
	}

	/**
	 * Delete codes.
	 *
	 * @param \WP_User $user
	 * @param string   $code_hashed
	 */
	public function delete_code( $user, $code_hashed ) {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODE_VALUES, true );
		// Delete the current code from the list since it's been used.
		$backup_codes = array_flip( $backup_codes );
		unset( $backup_codes[ $code_hashed ] );
		$backup_codes = array_values( array_flip( $backup_codes ) );
		// Update the backup code master list.
		update_user_meta( $user->ID, self::BACKUP_CODE_VALUES, $backup_codes );
	}

	/**
	 * @param int $count
	 *
	 * @return string
	 */
	public static function display_number_of_codes( $count ) {
		return sprintf(
		/* translators: %s: count */
			_n( '%s unused code remaining', '%s unused codes remaining', $count, 'wpdef' ),
			$count
		);
	}

	/**
	 * @param \WP_User $user
	 * @param string   $code
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_code( $user, $code ) {
		$backup_codes = get_user_meta( $user->ID, self::BACKUP_CODE_VALUES, true );

		if ( is_array( $backup_codes ) && ! empty( $backup_codes ) ) {
			foreach ( $backup_codes as $key => $code_hashed ) {
				if ( wp_check_password( $code, $code_hashed, $user->ID ) ) {
					$this->delete_code( $user, $code_hashed );

					return true;
				}
			}
		}

		return new \WP_Error( 'opt_fail', __( 'ERROR: Invalid verification code.', 'wpdef' ) );
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_authentication( $user ) {
		$backup_code = isset( $_POST['backup-codes'] )
			? sanitize_text_field( wp_unslash( $_POST['backup-codes'] ) )
			: false;

		return $this->validate_code( $user, filter_var( $backup_code, FILTER_SANITIZE_STRING ) );
	}
}
