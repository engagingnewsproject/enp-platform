<?php
/**
 * Handles the main settings.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Countable;
use WP_Defender\Event;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Backup_Settings;
use WP_Defender\Component\Config\Config_Adapter;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Setting\Main_Setting as Model_Main_Setting;
use WP_Filesystem_Base;

/**
 * Methods for handling main settings.
 */
class Main_Setting extends Event {

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-setting';

	/**
	 * The model for handling the data.
	 *
	 * @var Model_Main_Setting
	 */
	public $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Backup_Settings
	 */
	protected $service;

	/**
	 * Service for handling WPMUDEV.
	 *
	 * @var WPMUDEV
	 */
	protected $wpmudev;

	/**
	 * The intention/nonce action of the current request.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	protected $intention = '';

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_page(
			esc_html__( 'Settings', 'wpdef' ),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug
		);

		// Internal cache.
		$this->model   = new Model_Main_Setting();
		$this->service = wd_di()->get( Backup_Settings::class );
		$this->wpmudev = wd_di()->get( WPMUDEV::class );
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		$this->register_routes();

		// Add cron schedule to clean out outdated logs.
		add_action( 'wp_defender_clear_logs', array( $this, 'clear_logs' ) );
		add_action( 'admin_init', array( $this, 'check_cron_schedule' ) );
		add_action( 'wd_settings_update', array( $this, 'intercept_settings_update' ), 10, 2 );
	}

	/**
	 * Check actual config data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function check_configs(): Response {
		Config_Hub_Helper::clear_config_transient();

		return new Response( true, array() );
	}

	/**
	 * Safe way to get cached model.
	 *
	 * @return Model_Main_Setting
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return wd_di()->get( Model_Main_Setting::class );
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-settings', 'settings', $this->data_frontend() );
		wp_enqueue_script( 'def-settings' );
		$this->enqueue_main_assets();
	}

	/**
	 * Render the root element for frontend.
	 *
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$model = $this->get_model();
		$data  = $request->get_data();

		$model->import( $data );
		if ( $model->validate() ) {
			$this->set_intention( 'Settings' );
			$model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array(
					'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
					'auto_close' => true,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $model->get_formatted_errors(),
			)
		);
	}

	/**
	 * Reset settings.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function reset_settings(): Response {
		wd_di()->get( Advanced_Tools::class )->remove_settings();
		wd_di()->get( Audit_Logging::class )->remove_settings();
		wd_di()->get( Dashboard::class )->remove_settings();
		wd_di()->get( Security_Tweaks::class )->remove_settings();
		wd_di()->get( Scan::class )->remove_settings();
		// Parent and submodules.
		wd_di()->get( Firewall::class )->remove_settings();

		wd_di()->get( Mask_Login::class )->remove_settings();
		wd_di()->get( Notification::class )->remove_settings();
		wd_di()->get( Two_Factor::class )->remove_settings();
		wd_di()->get( Blocklist_Monitor::class )->remove_settings();
		$this->set_intention( 'Data Reset' );
		// Track first until settings are removed.
		$this->track_opt( false );
		$this->remove_settings();
		// Indicate that it is not a new installation.
		defender_no_fresh_install();

		return new Response(
			true,
			array(
				'message'  => esc_html__( 'Your settings have been reset.', 'wpdef' ),
				'redirect' => network_admin_url( 'admin.php?page=wp-defender' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * Tracks the settings toggle.
	 *
	 * @param  bool $active  The status of the toggle.
	 *
	 * @return void
	 */
	public function track_opt( $active ) {
		$model = $this->get_model();
		// Track only if the Data tracking option was enabled before changes.
		if ( $model->usage_tracking ) {
			$from = $this->get_triggered_location();
			$this->track_opt_toggle( $active, $from );
		}
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
		wd_di()->get( Model_Main_Setting::class )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$model = $this->get_model();

		$this->service->maybe_create_default_config();
		$configs = $this->get_configs_and_update_status();

		foreach ( $configs as &$config ) {
			// Unset the data as we don't need it.
			if ( isset( $config['configs'] ) ) {
				unset( $config['configs'] );
			}
		}

		$link           = $this->wpmudev->is_member()
			? 'https://wpmudev.com/translate/projects/wpdef/'
			: 'https://translate.wordpress.org/projects/wp-plugins/defender-security/';
		$can_whitelabel = wd_di()->get( \WP_Defender\Integrations\Dashboard_Whitelabel::class )->can_whitelabel();

		return array_merge(
			array(
				'general'       => array(
					'translate'           => $model->translate,
					'show_usage_tracking' => $this->wpmudev->is_wpmu_dev_admin() || ! $can_whitelabel,
					'usage_tracking'      => $model->usage_tracking,
					'translation_link'    => $link,
				),
				'data_settings' => array(
					'uninstall_settings'   => $model->uninstall_settings,
					'uninstall_data'       => $model->uninstall_data,
					'uninstall_quarantine' => $model->uninstall_quarantine,
				),
				'accessibility' => array(
					'high_contrast_mode' => $model->high_contrast_mode,
				),
				'misc'          => array(
					'setting_url'  => network_admin_url( is_multisite() ? 'settings.php' : 'options-general.php' ),
					'privacy_link' => Model_Main_Setting::PRIVACY_LINK,
				),
				'configs'       => $configs,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * Validates the importer data.
	 * This function checks if the importer data is valid by verifying its configuration data and comparing it with the
	 * sample data.
	 *
	 * @param  array $importer  The importer data to be validated.
	 *
	 * @return bool Returns true if the importer data is valid, false otherwise.
	 */
	private function validate_importer( $importer ): bool {
		if ( $this->service->verify_config_data( $importer ) ) {
			// Validate content. This is the current data, we use this for verify the schema.
			$sample = $this->service->gather_data();
			foreach ( $importer['configs'] as $slug => $module ) {
				// This is not in the sample, file is invalid.
				if ( ! isset( $sample[ $slug ] ) ) {
					return false;
				}

				$keys        = array_keys( $sample[ $slug ] );
				$import_keys = array_keys( $module );
				$diff        = array_diff( $import_keys, $keys );
				if ( count( $diff ) ) {
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Import config.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function import_config(): Response {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$file     = defender_get_data_from_request( 'file', 'f' );
		$tmp      = $file['tmp_name'];
		$content  = $wp_filesystem->get_contents( $tmp );
		$importer = json_decode( $content, true );
		if ( ! is_array( $importer ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'The file is corrupted.', 'wpdef' ),
				)
			);
		}

		// If it's old config structure then we upgrade configs to new format.
		if ( ! empty( $importer['configs'] ) && ! $this->service->check_for_new_structure( $importer['configs'] ) ) {
			$adapter             = wd_di()->get( Config_Adapter::class );
			$importer['configs'] = $adapter->upgrade( $importer['configs'] );
		}

		if ( ! $this->validate_importer( $importer ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__(
						'An error occurred while importing the file. Please check your file or upload another file.',
						'wpdef'
					),
				)
			);
		}

		// Do not use wp_strip_all_tags() to prevent XSS attack.
		$name    = sanitize_text_field( $importer['name'] );
		$configs = array(
			'name'         => $name,
			'immortal'     => false,
			'is_removable' => true,
		);

		$configs['configs']     = $importer['configs'];
		$configs['description'] = isset( $importer['description'] ) && ! empty( $importer['description'] )
		? sanitize_textarea_field( $importer['description'] )
		: '';
		$configs['strings']     = $this->service->import_module_strings( $importer );
		$key                    = 'wp_defender_config_import_' . time();
		update_site_option( $key, $configs );
		$this->service->index_key( $key );

		return new Response(
			true,
			array(
				'message' => sprintf(
				/* translators: %s: Config name. */
					esc_html__(
						'%s config has been uploaded successfully – you can now apply it to this site.',
						'wpdef'
					),
					'<strong>' . $name . '</strong>'
				),
				'configs' => Config_Hub_Helper::get_fresh_frontend_configs( $this->service ),
			)
		);
	}

	/**
	 * Create config.
	 *
	 * @param  Request $request  The request object containing new config data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function new_config( Request $request ): Response {
		$data = $request->get_data();
		$name = trim( $data['name'] );
		if ( empty( $name ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config name', 'wpdef' ),
				)
			);
		}
		$name     = sanitize_text_field( $name );
		$desc     = isset( $data['desc'] ) && ! empty( $data['desc'] ) ? wp_kses_post( $data['desc'] ) : '';
		$key      = 'wp_defender_config_' . time();
		$settings = $this->service->parse_data_for_import();
		$data     = array_merge(
			array(
				'name'         => $name,
				'immortal'     => false,
				'description'  => $desc,
				'is_removable' => true,
			),
			$settings
		);

		// Add config to HUB.
		$hub_id = Config_Hub_Helper::add_configs_to_hub( $data );

		if ( $hub_id ) {
			$data['hub_id'] = $hub_id;
		}

		unset( $data['labels'] );

		if ( update_site_option( $key, $data ) ) {
			$this->service->index_key( $key );

			return new Response(
				true,
				array(
					'message' => sprintf(
					/* translators: %s: Config name. */
						esc_html__( '%s config saved successfully.', 'wpdef' ),
						'<strong>' . $name . '</strong>'
					),
					'configs' => Config_Hub_Helper::get_fresh_frontend_configs( $this->service ),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => esc_html__(
						'An error occurred while saving your config. Please try it again.',
						'wpdef'
					),
				)
			);
		}
	}

	/**
	 * Download config
	 *
	 * @return Response|void
	 * @defender_route
	 */
	public function download_config() {
		$key = defender_get_data_from_request( 'key', 'g' );
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}
		$sample = $this->service->gather_data();
		foreach ( $sample as $slug => $data ) {
			foreach ( $data as $key => $val ) {
				if ( ! isset( $config['configs'][ $slug ][ $key ] ) ) {
					$config['configs'][ $slug ][ $key ] = null;
				}
			}
		}
		$filename = 'wp-defender-config-' . sanitize_file_name( $config['name'] ) . '.json';
		header( 'Content-disposition: attachment; filename=' . $filename );
		echo wp_json_encode( $config, JSON_PRETTY_PRINT );
		exit();
	}

	/**
	 * Apply config.
	 *
	 * @param  Request $request  The request object containing new config data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function apply_config( Request $request ) {
		$data = $request->get_data();
		$key  = trim( $data['key'] );
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}
		// Return error message or bool value for auth action.
		$restore_result = $this->service->restore_data( $config['configs'], 'plugin' );
		if ( is_string( $restore_result ) ) {
			return $this->apply_config_recommendations_error_message();
		}

		$this->service->make_config_active( $key );
		// Track.
		$this->track_feature(
			'def_config_applied',
			array(
				// The check is based on the fact that the Default config cannot be deleted.
				'Config Type' => isset( $config['is_removable'] ) && false === $config['is_removable'] ? 'Default' : 'Custom',
			)
		);

		$message = sprintf(
		/* translators: %s: Config name. */
			esc_html__(
				'%s config has been applied successfully.',
				'wpdef'
			),
			'<strong>' . $config['name'] . '</strong>'
		);
		$return = array();
		if ( $restore_result ) {
			$login_url           = wp_login_url();
			$settings_mask_login = new \WP_Defender\Model\Setting\Mask_Login();
			if ( $settings_mask_login->is_active() ) {
				$login_url = $settings_mask_login->get_new_login_url();
			}
			$message .= '<br/>' . sprintf(
			/* translators: %s: Login link. */
				esc_html__(
					'Due to currently applied security recommendations, you will now need to %s.',
					'wpdef'
				),
				'<a href="' . $login_url . '"><strong>' . esc_html__( 're-login', 'wpdef' ) . '</strong></a>'
			);
			$message .= '<br/>';
			$message .= esc_html__( 'This will auto reload now.', 'wpdef' );

			$return['reload'] = 3;
			$redirect         = rawurlencode( network_admin_url( 'admin.php?page=wdf-setting&view=configs' ) );
			if ( isset( $data['screen'] ) && 'dashboard' === $data['screen'] ) {
				$redirect = rawurlencode( network_admin_url( 'admin.php?page=wp-defender' ) );
			}
			$return['redirect'] = add_query_arg(
				'redirect_to',
				$redirect,
				$login_url
			);
			$return['interval'] = 2;
		}

		$return['message']    = $message;
		$return['auto_close'] = true;
		$return['configs']    = Config_Hub_Helper::get_fresh_frontend_configs( $this->service );

		return new Response( true, $return );
	}

	/**
	 * Update config.
	 *
	 * @param  Request $request  The request object containing new config data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function update_config( Request $request ) {
		$data        = $request->get_data();
		$key         = trim( $data['key'] );
		$name        = trim( $data['name'] );
		$description = trim( $data['desc'] );
		if ( empty( $name ) || empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$old_config            = $config;
		$config['name']        = sanitize_text_field( $name );
		$config['description'] = sanitize_textarea_field( $description );

		// Check data has been changed or not.
		if (
			$old_config['name'] === $config['name'] &&
			$old_config['description'] === $config['description']
		) {
			// Data is not changed, so not need to run update query.
			$option_updated = true;
		} else {
			$option_updated = update_site_option( $key, $config );
			Config_Hub_Helper::update_on_hub( $config );
		}

		if ( $option_updated ) {
			return new Response(
				true,
				array(
					'message'    => sprintf(
					/* translators: %s: Config name. */
						esc_html__( '%s config saved successfully.', 'wpdef' ),
						'<strong>' . $name . '</strong>'
					),
					'auto_close' => true,
					'configs'    => Config_Hub_Helper::get_fresh_frontend_configs( $this->service ),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => esc_html__(
						'An error occurred while saving your config. Please try it again.',
						'wpdef'
					),
				)
			);
		}
	}

	/**
	 * Delete config.
	 *
	 * @param  Request $request  The request object containing config key.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function delete_config( Request $request ) {
		$data = $request->get_data();
		$key  = trim( $data['key'] );
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( isset( $config['is_removable'] ) && ! $config['is_removable'] ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Config can\'t be removed', 'wpdef' ),
				)
			);
		}

		// Remove from HUB.
		if ( isset( $config['hub_id'] ) ) {
			Config_Hub_Helper::delete_configs_from_hub( (int) $config['hub_id'] );
		}

		if ( 0 === strpos( $key, 'wp_defender_config' ) ) {
			delete_site_option( $key );
			$this->service->clear_keys();

			return new Response(
				true,
				array(
					'message'    => esc_html__( 'Config removed successfully.', 'wpdef' ),
					'auto_close' => true,
					'configs'    => Config_Hub_Helper::get_fresh_frontend_configs( $this->service ),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'Invalid config', 'wpdef' ),
			)
		);
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Update config status and return them.
	 *
	 * @return array
	 */
	private function get_configs_and_update_status() {
		$configs   = Config_Hub_Helper::get_configs( $this->service );
		$is_remove = Config_Hub_Helper::check_remove_active_flag();

		// Loop to update strings of configs.
		foreach ( $configs as $key => &$config ) {
			if ( ! is_array( $config ) ) {
				continue;
			}

			$config['strings'] = $this->service->import_module_strings( $config );

			if ( $is_remove ) {
				$config['is_active'] = false;
			}

			// Update config data.
			update_site_option( $key, $config );
		}

		return $configs;
	}

	/**
	 * Generates an error message for when there is an issue applying some tweaks from the Recommendations tab.
	 *
	 * @return Response The response object containing the error message and fresh frontend configurations.
	 */
	private function apply_config_recommendations_error_message(): Response {
		$message = sprintf(
		/* translators: 1: Recommendations tab, 2: wp-config.php file, 3: Documentation. */
			esc_html__(
				'There was an issue with applying some of the tweaks from the %1$s tab because we cannot make changes to your %2$s file. Please see our %3$s to apply the changes manually.',
				'wpdef'
			),
			'<strong>' . esc_html__( 'Recommendations', 'wpdef' ) . '</strong>',
			'<strong>' . esc_html__( 'wp-config.php', 'wpdef' ) . '</strong>',
			'<a href="' . WP_DEFENDER_DOCS_LINK . '#manually-applying-recommendations" target="_blank">' . esc_html__( 'documentation', 'wpdef' ) . '</a>'
		);

		return new Response(
			false,
			array(
				'message' => $message,
				'configs' => Config_Hub_Helper::get_fresh_frontend_configs( $this->service ),
			)
		);
	}

	/**
	 * Check if the logger cron is scheduled to run.
	 *
	 * @return void
	 */
	public function check_cron_schedule(): void {
		if ( ! wp_next_scheduled( 'wp_defender_clear_logs' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wp_defender_clear_logs' );
		}
	}

	/**
	 * Clear out lines that are older than 30 days.
	 *
	 * @return void
	 */
	public function clear_logs(): void {
		// since 2.7.0.
		$time_limit = apply_filters( 'wpdef_clear_logs_time_limit', MONTH_IN_SECONDS );

		if ( is_multisite() ) {
			global $wpdb;
			$offset = 0;
			$limit  = 100;
			$blogs  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} LIMIT %d, %d", $offset, $limit ),
				ARRAY_A
			);
			while ( ! empty( $blogs ) && is_array( $blogs ) ) {
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog['blog_id'] );

					$this->clear_logs_from_files( $time_limit );

					restore_current_blog();
				}
				$offset += $limit;
				$blogs   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} LIMIT %d, %d", $offset, $limit ),
					ARRAY_A
				);
			}
		} else {
			$this->clear_logs_from_files( $time_limit );
		}
	}

	/**
	 * Clear log files older than the specified time.
	 *
	 * @param  int $time_limit  The time limit in seconds.
	 *
	 * @return void
	 * @since 2.7.0
	 */
	public function clear_logs_from_files( int $time_limit = MONTH_IN_SECONDS ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$now   = wp_date( 'c' );
		$files = array( wd_internal_log() );

		foreach ( $files as $file_name ) {
			$file_path = $this->get_log_path( $file_name );

			if ( ! file_exists( $file_path ) ) {
				return;
			}

			$content         = file( $file_path );
			$size_of_content = is_array( $content ) || $content instanceof Countable ? count( $content ) : 0;

			foreach ( $content as $index => $line ) {
				// If the line does not start with '[' (it's probably not a new entry).
				$first_char = substr( $line, 0, 1 );

				if ( '[' !== $first_char ) {
					// Delete.
					unset( $content[ $index ] );
				}

				/**
				 * Get the date from entry. Items can be an array it two cases - if there's a valid date, or if the line
				 * contained something like [header] in the start. Cannot make assumptions just on the fact it's an array.
				 */
				preg_match( '/\[(.*)\]/', $line, $items );

				// If, for some reason, can't get the date, or it's not the size of an ISO 8601 date.
				if ( ! isset( $items[1] ) || 25 !== strlen( $items[1] ) ) {
					// Delete.
					unset( $content[ $index ] );
				} else {
					// It looks like it's a valid date string, compare with today.
					$time_diff = strtotime( $now ) - strtotime( $items[1] );

					// We don't need to continue on, because if this entry is not older than specific time, the next one will not be as well.
					if ( $time_diff < $time_limit ) {
						break;
					}

					unset( $content[ $index ] );
				}
			}

			// Nothing changed - do nothing.
			if ( ( is_array( $content ) || $content instanceof Countable ? count( $content ) : 0 ) === $size_of_content ) {
				return;
			}

			// Glue back together and write back to file.
			$content = implode( '', $content );

			$wp_filesystem->put_contents( $file_path, $content );
		}
	}

	/**
	 * Track the data if there are settings changes.
	 *
	 * @param  array $old_settings  Old settings.
	 * @param  array $new_settings  New settings.
	 *
	 * @return void
	 * @since 4.2.0
	 */
	public function intercept_settings_update( $old_settings, $new_settings ) {
		$from = $this->get_triggered_location();
		if (
			'' !== $from
			&& isset( $new_settings['usage_tracking'], $old_settings['usage_tracking'] )
			&& $new_settings['usage_tracking'] !== $old_settings['usage_tracking']
		) {
			$this->track_opt_toggle( ! empty( $new_settings['usage_tracking'] ), $from );
		}
	}
}