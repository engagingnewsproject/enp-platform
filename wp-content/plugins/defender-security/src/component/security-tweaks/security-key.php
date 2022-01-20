<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Component\Response;
use WP_Error;
use Calotes\Base\Component;
use WP_Defender\Model\Setting\Mask_Login;
use WP_Defender\Model\Setting\Security_Tweaks;

/**
 * Class Security_Key
 * @package WP_Defender\Component\Security_Tweaks
 */
class Security_Key extends Component {
	public $slug              = 'security-key';
	public $default_days      = '60 days';
	public $reminder_duration = null;
	public $reminder_date     = null;
	public $last_modified     = null;
	public $file;

	/**
	 * Prefix used in db option meta key.
	 *
	 * @var string
	 */
	private $option_prefix = 'defender_security_tweaks_';

	/**
	 * Flag to automate the security key/salt generation.
	 *
	 * @var bool
	 */
	private $is_autogenerate_keys = true;

	public function __construct() {
		$this->file = defender_wp_config_path();
		$this->add_hooks();
	}

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		$this->get_options();

		if ( ! $this->is_salts_exist() ) {
			return false;
		}

		if ( ! $this->last_modified ) {
			if ( file_exists( $this->file ) ) {
				$this->last_modified = filemtime( $this->file );
			} else {
				$this->last_modified = filemtime( ABSPATH . WPINC . '/general-template.php' );
			}
		}

		if ( $this->last_modified ) {
			$reminder_date = strtotime( '+' . $this->reminder_duration, $this->last_modified );

			return $reminder_date > time();
		}
	}

	/**
	 * Get options.
	 *
	 * @return void
	 */
	private function get_options() {
		$options                 = get_site_option( 'defender_security_tweaks_' . $this->slug );
		$this->reminder_date     = ! empty( $options['reminder_date'] ) ? $options['reminder_date'] : null;
		$this->reminder_duration = ! empty( $options['reminder_duration'] ) ? $options['reminder_duration'] : $this->default_days;
		$this->last_modified     = ! empty( $options['last_modified'] ) ? $options['last_modified'] : null;

		$this->is_autogenerate_keys = ! empty( $options['is_autogenerate_keys'] )
			? (bool) $options['is_autogenerate_keys']
			: true;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool|WP_Error
	 */
	public function process() {
		if ( ! is_writable( $this->file ) ) {
			return new WP_Error(
				'defender_file_not_writable',
				/* translators: %s: file path */
				sprintf( __( 'The file %s is not writable', 'wpdef' ), $this->file )
			);
		}

		$constants = $this->get_constants();
		$salts     = $this->get_salts();

		if ( is_wp_error( $salts ) ) {
			return $salts;
		}

		$contents  = file_get_contents( $this->file );
		$new_salts = '';

		foreach ( $constants as $key => $const ) {
			if ( defined( $const ) ) {
				$pattern     = "/^define\(\s*['|\"]{$const}['|\"],(.*)\)\s*;/m";
				$replacement = $salts[ $key ];
				$contents    = preg_replace_callback(
					$pattern,
					function () use ( $replacement ) {
						return $replacement;
					},
					$contents
				);
			} else {
				$new_salts .= $salts[ $key ] . PHP_EOL;
			}
		}

		if ( ! empty( $new_salts ) ) {
			$new_salts = PHP_EOL .
				'/* DEFENDER GENERATED SALTS */' .
				PHP_EOL .
				$new_salts .
				PHP_EOL;

			$contents = $this->append_salts( $contents, $new_salts );
		}

		$is_done = (bool) file_put_contents( $this->file, $contents, LOCK_EX );

		if ( $is_done ) {
			$values                  = get_site_option( 'defender_security_tweaks_' . $this->slug );
			$values['last_modified'] = time();
			update_site_option( 'defender_security_tweaks_' . $this->slug, $values );

			$url        = wp_login_url( network_admin_url( 'admin.php?page=wdf-hardener' ) );
			$mask_login = new Mask_Login();

			if ( $mask_login->is_active() ) {
				$url = $mask_login->get_new_login_url();
			}

			$interval = 3;
			return new Response(
				true,
				array(
					'message'  => sprintf(
					/* translators: %s: login url, %d: number of seconds */
						__(
							'All key salts have been regenerated. You will now need to <a href="%1$s"><strong>re-login</strong></a>.<br/>This will auto reload after <span class="hardener-timer">%2$s</span> seconds.',
							'wpdef'
						),
						$url,
						$interval
					),
					'redirect' => $url,
					'interval' => $interval,
				)
			);
		}

		return $is_done;
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Get salts to be placed in wp-config.php.
	 *
	 * @return string|WP_Error
	 */
	private function get_salts() {
		$response = wp_safe_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'defender_salts_not_found',
				__( 'Unable to generate salts. Please try again.', 'wpdf' )
			);
		}

		return array_filter( explode( "\n", wp_remote_retrieve_body( $response ) ) );
	}

	/**
	 * Get how long the wp-config file is last updated.
	 *
	 * @return string
	 */
	private function get_last_modified_days() {
		$timestamp    = filemtime( ABSPATH . '/' . WPINC . '/general-template.php' );
		$current_time = time();
		$days_ago     = '';

		if ( $this->last_modified ) {
			$days_ago = ( $current_time - $this->last_modified ) / DAY_IN_SECONDS;
		} elseif ( $timestamp ) {
			$days_ago = ( $current_time - $timestamp ) / DAY_IN_SECONDS;
		}

		$days_ago = $days_ago ? round( $days_ago ) : __( 'unknown', 'wpdef' );

		return $days_ago ? $days_ago : 1;
	}

	/**
	 * Get all the constants.
	 *
	 * @return array
	 */
	private function get_constants() {
		return array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		);
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		$get_last_modified_days = $this->get_last_modified_days();

		if ( 'unknown' === $get_last_modified_days ) {
			$error_message = __(
				'We can\'t tell how old your security keys are, perhaps it\'s time to update them?',
				'wpdef'
			);
		}
		if ( ! $this->is_salts_exist() ) {
			$error_message = __(
				'One or more security salts aren\'t defined in wp-config.php. Time to regenerate them!',
				'wpdef'
			);
		} else {
			$error_message = sprintf(
			/* translators: %s: number of days */
				__( 'Your current security keys are %s days old. Time to update them!', 'wpdef' ),
				$get_last_modified_days
			);
		}

		return array(
			'slug'             => $this->slug,
			'title'            => __( 'Update old security keys', 'wpdef' ),
			'errorReason'      => $error_message,
			'successReason'    => sprintf(
			/* translators: %s: number of days */
				__( 'Your security keys are less than %s days old, nice work.', 'wpdef' ),
				$get_last_modified_days
			),
			'misc'             => array(
				'reminder' => $this->reminder_duration,
			),
			'bulk_description' => __(
				'Your current security keys are unknown days old. Time to update them! We will update the frequency to 60 days.',
				'wpdef'
			),
			'bulk_title'       => __( 'Security Keys', 'wpdef' ),
		);
	}

	/**
	 * Getter method of is_autogenerate_keys.
	 *
	 * @return bool. Return true or false which is used to trigger auto generation of security salt/key.
	 */
	public function get_is_autogenerate_keys() {
		$is_autogenerate_keys = $this->get_option( 'is_autogenerate_keys' );

		$this->is_autogenerate_keys = is_null( $is_autogenerate_keys )
			? true
			: (bool) $is_autogenerate_keys;

		return $this->is_autogenerate_keys;
	}

	/**
	 * Setter method of is_autogenerate_keys.
	 *
	 * @param bool $value Boolean flag of the is_autogenerate_keys.
	 *
	 * @return bool Return true if value updated, otherwise false.
	 */
	public function set_is_autogenrate_keys( $value ) {
		$this->is_autogenerate_keys = (bool) $value;

		return $this->update_option(
			'is_autogenerate_keys',
			$this->is_autogenerate_keys
		);
	}

	/**
	 * Generic method to update security key site option.
	 *
	 * @param string $key   Name of the security key option.
	 * @param mixed  $value Value of the security key option.
	 *
	 * @return bool True if the value was updated, false otherwise.
	 */
	public function update_option( $key, $value ) {
		$option_name = $this->option_prefix . $this->slug;

		$options = get_site_option( $option_name );

		$options[ $key ] = $value;

		return update_site_option( $option_name, $options );
	}

	/**
	 * Generic method to get security key site option value.
	 *
	 * @param string $key Name of the security key option.
	 *
	 * @return mixed Value of the security key option.
	 */
	public function get_option( $key ) {
		$option_name = $this->option_prefix . $this->slug;

		$options = get_site_option( $option_name, array() );

		return array_key_exists( $key, $options ) ? $options[ $key ] : null;
	}

	/**
	 * Schedules time to trigger regenerate security keys/salts.
	 *
	 * @param array $schedules List of cron schedules.
	 *
	 * @return array List of cron schedules.
	 */
	public function cron_schedules( $schedules ) {
		// 30 days.
		if ( ! isset( $schedules['thirty_days'] ) ) {
			$schedules['thirty_days'] = array(
				'interval' => 2592000,
				'display'  => __( '30 days', 'wpdef' ),
			);
		}

		// 60 days.
		if ( ! isset( $schedules['sixty_days'] ) ) {
			$schedules['sixty_days'] = array(
				'interval' => 5184000,
				'display'  => __( '60 days', 'wpdef' ),
			);
		}

		// 90 days.
		if ( ! isset( $schedules['ninety_days'] ) ) {
			$schedules['ninety_days'] = array(
				'interval' => 7776000,
				'display'  => __( '90 days', 'wpdef' ),
			);
		}

		// 6 months.
		if ( ! isset( $schedules['six_months'] ) ) {
			$schedules['six_months'] = array(
				'interval' => 15780000,
				'display'  => __( '6 months', 'wpdef' ),
			);
		}

		// 1 year.
		if ( ! isset( $schedules['one_year'] ) ) {
			$schedules['one_year'] = array(
				'interval' => 31536000,
				'display'  => __( '1 year', 'wpdef' ),
			);
		}

		return $schedules;
	}

	/**
	 * Method to initialize component hooks.
	 */
	public function add_hooks() {
		add_filter( 'cron_schedules', array( &$this, 'cron_schedules' ) );
		add_action( 'wpdef_sec_key_gen', array( &$this, 'cron_process' ) );
	}

	/**
	 * Cron schedule.
	 */
	public function cron_schedule() {
		if (
			true === $this->get_is_autogenerate_keys() &&
			! wp_next_scheduled( 'wpdef_sec_key_gen' )
		) {
			$display_name = $this->get_option( 'reminder_duration' );

			if ( empty( $display_name ) ) {
				$display_name = $this->default_days;
			}

			$schedule_detail = $this->get_schedule_detail( $display_name );

			$schedule_key      = key( $schedule_detail );
			$schedule_interval = time() + $schedule_detail[ $schedule_key ]['interval'];

			wp_schedule_event(
				$schedule_interval,
				$schedule_key,
				'wpdef_sec_key_gen'
			);
		}
	}

	/**
	 * Cron unschedule.
	 */
	public function cron_unschedule() {
		$timestamp = wp_next_scheduled( 'wpdef_sec_key_gen' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpdef_sec_key_gen' );
		}
	}

	/**
	 * Cron schedule security key/salt generation process.
	 */
	public function cron_process() {
		try {
			$security_tweak_model = new Security_Tweaks();
			if ( ! $security_tweak_model->is_tweak_ignore( $this->slug ) &&
					true === $this->get_is_autogenerate_keys()
			) {
				$this->process();
			}
		} catch ( \Throwable $th ) {
			// PHP 7+ catch.
			$this->log( get_class( $th ) . ': ' . $th->getMessage(), 'internal.log' );
		} catch ( \Exception $e ) {
			$this->log( get_class( $e ) . ': ' . $e->getMessage(), 'internal.log' );
		}
	}

	/**
	 * Get scheduler detail.
	 *
	 * @param string $display_name The name to filter the schedules list.
	 *
	 * @return array Return filtered schedule detail.
	 */
	private function get_schedule_detail( $display_name ) {
		$all_schedules = wp_get_schedules();

		$schedule_detail = array_filter(
			$all_schedules,
			function( $arr ) use ( $display_name ) {
				return $display_name === $arr['display'];
			}
		);

		return $schedule_detail;
	}

	/**
	 * Check all salts are exists.
	 *
	 * @return bool Return true if all constants have salt else false.
	 */
	private function is_salts_exist() {
		foreach ( $this->get_constants() as $constants ) {
			if ( ! defined( $constants ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Append salt to wp-config.php file content string.
	 *
	 * @param string $contents  Text content of wp-config.php.
	 * @param string $new_salts Salts need to be appended.
	 *
	 * @return string Text content after salts appended.
	 */
	private function append_salts( $contents, $new_salts ) {
		$config_array = preg_split( '/\R/', $contents );

		$line_no = $this->search_line( // Important $line_no is array index therefore 0 means first line.
			$config_array,
			"That's all, stop editing!" // Warning text in wp-config file.
		);

		if ( false === $line_no ) { // If faster prediction failed.
			// Regex prediction (slow one but last resort). Matches multiline of: "if ( ! defined( 'ABSPATH' ) ) {".
			$regex = "/^\s*if\s*\(\s*\!\s*defined\s*\(\s*['|\"]ABSPATH['|\"]\s*\)\s*\)\s*\{?\s*/m";

			$line_no = $this->grep_line( $config_array, $regex );
		}

		// Splice before WP initialize and append.
		array_splice( $config_array, $line_no, 0, $new_salts );

		return implode( PHP_EOL, $config_array );
	}

	/**
	 * Simple array search and return the array index of first match value.
	 *
	 * @param array  $array       Array of text for search.
	 * @param string $search_text Search term.
	 *
	 * @return int|bool On found return array index else false.
	 */
	private function search_line( $array, $search_text ) {
		$filtered_array = array_filter(
			$array,
			function( $el ) use ( $search_text ) {
				return ( false !== strpos( $el, $search_text ) );
			}
		);

		if ( empty( $filtered_array ) ) {
			return false;
		}

		reset( $filtered_array );
		return key( $filtered_array );
	}

	/**
	 * Grep array search and return the array index of first match value.
	 *
	 * @param array  $array Array of text for search.
	 * @param string $regex Search term (accepts regex).
	 *
	 * @return int|bool On found return array index else false.
	 */
	private function grep_line( $array, $regex ) {
		$filtered_array = preg_grep( $regex, $array );

		if ( empty( $filtered_array ) ) {
			return false;
		}

		reset( $filtered_array );
		return key( $filtered_array );
	}

	/**
	 * @return array
	 */
	public function reminder_frequencies() {

		return array(
			'30 days',
			'60 days',
			'90 days',
			'6 months',
			'1 year',
		);
	}
}
