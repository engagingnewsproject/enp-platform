<?php


class Ga_SupportLogger {
	const LOG_OPTION = 'googleanalytics_sherethis_error_log';

	static $debug_info;
	static $debug_help_message;

	/**
	 * Constructor.
	 * @return void
	 */
	public function __construct() {
		add_action( 'st_support_show_button', array( $this, 'display_button' ) );
		add_action( 'st_support_save_error',  array( $this, 'save_error' ) );
		$this->get_debug_body();
	}

	/**
	 * Displays a button to email the debugging info.
	 * @return void
	 */
	public function display_button() {
		printf(
			'<a href="%s" class="button button-secondary" target="_blank">Get Debugging Info</a>',
			esc_url( '' )
		);
	}


	/**
	 * Saves an error to the log.
	 * @param Exception $err Error to save.
	 * @return void
	 */
	public function save_error( Exception $err ) {
		$cur_log = get_option( self::LOG_OPTION, array() );

		// Creates the error object.
		$new_log = array(
			'message' => $err->getMessage(),
			'stack' => $err->getTraceAsString(),
			'date' => current_time( 'r' ),
		);

		if ( method_exists( $err, 'get_google_error_response' ) ) {
			$new_log['response'] = $err->get_google_error_response();
		}

		$cur_log[] = $new_log;

		// Cap the log at 20 entries for space purposes.
		if ( count( $cur_log ) > 20 ) {
			array_pop( $cur_log );
		}

		// Save.
		update_option( self::LOG_OPTION, $cur_log );
	}

	public function get_debug_body() {
		$debug_error = $this->get_formatted_log();

		if ( 'None' === $debug_error ) {
			self::$debug_info = false;

			return;
		}

		$debug_message = $this->get_formatted_message();

		$debug_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		$let_debug_message = '<br> If you are still experiencing the issue after that click <a href="' . $debug_link . '&sdb=true" id="debug-message">here</a>';

		$debug_help_message = !empty($_GET['sdb']) ? false : $this->get_debug_help_message($debug_message);

		if (isset($debug_help_message['message'])) {
			$body = $debug_help_message['message'];
			$body .= $debug_help_message['let-debug'] ? $let_debug_message : '';

			self::$debug_info = ['message' => $body, 'debug' => $debug_help_message['let-debug']];
		} else {
			$body = 'Debug Info:' . PHP_EOL . PHP_EOL;
			$body .= implode( $this->get_debug_info(), PHP_EOL );
			$body .= PHP_EOL . PHP_EOL . 'Error Log:' . PHP_EOL . PHP_EOL;
			$body .= esc_html( $debug_error );

			self::$debug_info = $body;
		}
	}

	public function get_debug_help_message($error) {

		switch ($error) {
			case 'invalid_grant':
				return [
					'message'   => 'Hi! It looks like you submitted the wrong authentication grant. Please try again by re-authenticating.',
					'let-debug' => true
				];
				break;
			case 'SSL certificate problem: unable to get local issuer certificate (60)':
				return [
					'message'   => 'Hi! Please check your site\'s SSL certificate. A functioning SSL certificate is required',
					'let-debug' => false
				];
				break;
			case 'SSL certificate problem: unable to get local issuer certificate':
				return [
					'message'   => 'Hi! Please check your site\'s SSL certificate. A functioning SSL certificate is required',
					'let-debug' => false
				];
				break;
			case 'User does not have any Google Analytics account.':
				return [
					'message'   => 'Hi! Looks like we’re not able to find a Google Analytics account. Please double check to make sure the Google account you used to authenticate with has a working Google Analytics account setup.',
					'let-debug' => false
				];
				break;
			case 'SSL certificate problem: certificate has expired (60)':
				return [
					'message'   => 'Hi! Please check your site\'s SSL certificate. A functioning SSL certificate is required',
					'let-debug' => false
				];
				break;
			case 'SSL certificate problem, verify that the CA cert is OK':
				return [
					'message'   => 'Hi! Please check your site\'s SSL certificate. A functioning SSL certificate is required',
					'let-debug' => false
				];
				break;
		}

		return [
			'message' => 'Hi! It appears something went wrong. We apologize for the inconvenience! Please try to re-authenticate your Google account and verify your site has a proper SSL certficiate.',
			'let-debug' => true
		];
	}

	/**
	 * Gets an array of debugging information about the current system.
	 * @return array
	 */
	private function get_debug_info() {
		$theme   = wp_get_theme();
		$plugins = wp_get_active_and_valid_plugins();

		$data = array(
			'Plugin Version' => GOOGLEANALYTICS_VERSION,
			'WordPress Version' => get_bloginfo( 'version' ),
			'PHP Version' => phpversion(),
			'CURL Version' => $this->get_curl_version(),
			'Site URL' => get_bloginfo( 'wpurl' ),
			'Theme Name' => $theme->get( 'Name' ),
			'Theme URL' => $theme->get( 'ThemeURI' ),
			'Theme Version' => $theme->get( 'Version' ),
			'Active Plugins' => implode( $plugins, ', ' ),
			'Operating System' => $this->get_operating_system(),
			'Web Server' => $_SERVER['SERVER_SOFTWARE'],
			'Current Time' => current_time( 'r' ),
			'Browser' => !empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'Excluded roles' =>  get_option( 'googleanalytics_exclude_roles' ),
			'Manually Tracking ID enabled' => get_option( 'googleanalytics_web_property_id_manually' ),
			'Manually typed Tracking ID' => get_option( 'googleanalytics_web_property_id_manually_value' ),
			'Tracking ID' => get_option( 'googleanalytics_web_property_id' ),
		);
		$formatted = array();
		foreach ( $data as $text => $value ) {
			$formatted[] = sprintf(
				__( $text ) . ': %s',
				$value
			);
		}
		return $formatted;
	}

	/**
	 * Gets CURL version
	 * @return string
	 */
	private function get_curl_version(){
		$curl_version = curl_version();
		return !empty( $curl_version['version'] ) ? $curl_version['version'] : '';
	}

	/**
	 * Gets operating system
	 * @return string
	 */
	private function get_operating_system(){
		if ( function_exists( 'ini_get' ) ) {
			$disabled = explode( ',', ini_get( 'disable_functions' ) );
			return !in_array( 'php_uname', $disabled ) ? php_uname() : PHP_OS;
		}
		return PHP_OS;
	}

	/**
	 * Gets a string of formatted error log entries.
	 * @return string
	 */
	private function get_formatted_log() {
		$log = get_option( self::LOG_OPTION );
		if ( ! $log ) {
			return 'None';
		}

		$text = '';
		foreach ( $log as $error ) {
			foreach ( $error as $key => $value ) {
				$text .= ucwords( $key ) . ': ' . $value . "\n";
			}
		}

		return $text;
	}

	/**
	 * Gets a string of formatted of just the message
	 * @return string
	 */
	private function get_formatted_message() {
		$log = get_option( self::LOG_OPTION );
		if ( ! $log ) {
			return 'None';
		}

		return isset($log[0]['message']) ? $log[0]['message'] : '';
	}

}

new Ga_SupportLogger();
