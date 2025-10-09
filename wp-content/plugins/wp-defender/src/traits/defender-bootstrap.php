<?php
/**
 * Handle common bootstrap functionalities.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_CLI;
use Calotes\DB\Mapper;
use WP_Defender\Admin;
use WP_Defender\Component\Cli;
use Calotes\Helper\Array_Cache;
use WP_Defender\Controller\HUB;
use WP_Defender\Controller\WAF;
use WP_Defender\Controller\Scan;
use WP_Defender\Component\Crypt;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Controller\Onboard;
use WP_Defender\Controller\Webauthn;
use WP_Defender\Controller\Dashboard;
use WP_Defender\Controller\Recaptcha;
use WP_Defender\Controller\Mask_Login;
use WP_Defender\Controller\Quarantine;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Component\Hub_Connector;
use WP_Defender\Controller\Main_Setting;
use WP_Defender\Controller\Notification;
use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Controller\Data_Tracking;
use WP_Defender\Controller\Advanced_Tools;
use WP_Defender\Controller\Password_Reset;
use WP_Defender\Controller\Strong_Password;
use WP_Defender\Controller\Expert_Services;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Controller\Blocklist_Monitor;
use WP_Defender\Controller\Session_Protection;
use WP_Defender\Controller\Password_Protection;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Component\Logger\Rotation_Logger;
use WP_Defender\Component\Firewall as Firewall_Component;
use WP_Defender\Controller\Firewall as Firewall_Controller;
use WP_Defender\Controller\Hub_Connector as Hub_Connector_Controller;
use WP_Defender\Model\Onboard as Onboard_Model;
use WP_Defender\Controller\Rate as Rate_Controller;
use WP_Defender\Component\Rate as Rate_Component;

trait Defender_Bootstrap {
	/**
	 * Table name for quarantine.
	 *
	 * @var string
	 */
	private $quarantine_table = 'defender_quarantine';

	/**
	 * Table name for scan item.
	 *
	 * @var string
	 */
	private $scan_item_table = 'defender_scan_item';

	/**
	 * Check is all quarantine dependent table is having storage engine InnoDB.
	 *
	 * @return bool True if all dependent table is InnoDB else false.
	 */
	private function is_quarantine_dependent_tables_innodb(): bool {
		global $wpdb;

		$tables      = array( $wpdb->users, $wpdb->base_prefix . $this->scan_item_table );
		$total_table = count( $tables );

		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(`ENGINE`) = %d FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND `ENGINE` = %s AND TABLE_NAME IN ( '{$wpdb->users}', '{$wpdb->base_prefix}defender_scan_item' );",
				$total_table,
				$wpdb->dbname,
				'innodb',
			)
		) === '1';
	}

	/**
	 * Creates the quarantine table if it doesn't exist.
	 *
	 * @return void
	 */
	public function create_table_quarantine() {
		global $wpdb;

		// Define table names and charset.
		$quarantine_table = $wpdb->base_prefix . $this->quarantine_table;
		$scan_item_table  = $wpdb->base_prefix . $this->scan_item_table;
		$charset_collate  = $wpdb->get_charset_collate();
		$unique_id        = uniqid( $wpdb->prefix );

		$common_columns = <<<SQL
		`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
		`defender_scan_item_id` int UNSIGNED DEFAULT NULL,
		`file_hash` char(53) NOT NULL,
		`file_full_path` text NOT NULL,
		`file_original_name` tinytext NOT NULL,
		`file_extension` varchar(16) DEFAULT NULL,
		`file_mime_type` varchar(64) DEFAULT NULL,
		`file_rw_permission` smallint UNSIGNED DEFAULT NULL,
		`file_owner` varchar(255) DEFAULT NULL,
		`file_group` varchar(255) DEFAULT NULL,
		`file_version` varchar(32) DEFAULT NULL,
		`file_category` tinyint UNSIGNED DEFAULT 0,
		`file_modified_time` datetime NOT NULL,
		`source_slug` varchar(255) NOT NULL,
		`created_time` datetime NOT NULL,
		`created_by` bigint UNSIGNED DEFAULT NULL,
		PRIMARY KEY (`id`)
		SQL;

		// Define key names.
		$scan_item_key  = "{$unique_id}_defender_scan_item_id";
		$created_by_key = "{$unique_id}_created_by";

		// Build the SQL statement based on the storage engine.
		if ( $this->is_quarantine_dependent_tables_innodb() ) {
			$sql = <<<SQL
			CREATE TABLE IF NOT EXISTS `{$quarantine_table}` (
				$common_columns,
				CONSTRAINT `{$scan_item_key}`
				FOREIGN KEY (`defender_scan_item_id`) REFERENCES {$scan_item_table}(`id`)
				ON UPDATE CASCADE ON DELETE SET NULL,
				CONSTRAINT `{$created_by_key}`
				FOREIGN KEY (`created_by`) REFERENCES {$wpdb->users}(`ID`)
				ON UPDATE CASCADE ON DELETE SET NULL
			) {$charset_collate};
			SQL;
		} else {
			$sql = <<<SQL
			CREATE TABLE IF NOT EXISTS `{$quarantine_table}` (
				$common_columns,
				KEY `{$scan_item_key}` (`defender_scan_item_id`),
				KEY `{$created_by_key}` (`created_by`)
			) {$charset_collate};
			SQL;
		}

		// Execute the SQL query.
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Activation.
	 */
	private function activation_hook_common(): void {
		$this->create_database_tables();
		$this->on_activation();
		// Create a file with a random key if it doesn't exist.
		( new Crypt() )->create_key_file();
		// If this is a plugin reactivating, then track it. No need the check by 'wd_nofresh_install' key because the option is disabled by default.
		$settings = wd_di()->get( Main_Setting::class );
		$settings->set_intention( 'Reactivation' );
		$settings->track_opt( true );

		$service = wd_di()->get( Firewall_Component::class );
		$service->auto_switch_ip_detection_option();
		$service->maybe_show_misconfigured_ip_detection_option_notice();
		$service->maybe_dismiss_cf_notice();
		wp_schedule_single_event( time() + 5, 'wpdef_smart_ip_detection_ping' );
	}

	/**
	 * Deactivation.
	 */
	public function deactivation_hook(): void {
		wp_clear_scheduled_hook( 'firewall_clean_up_logs' );
		wp_clear_scheduled_hook( 'audit_sync_events' );
		wp_clear_scheduled_hook( 'audit_clean_up_logs' );
		wp_clear_scheduled_hook( 'wdf_maybe_send_report' );
		wp_clear_scheduled_hook( 'wp_defender_clear_logs' );
		wp_clear_scheduled_hook( 'wpdef_sec_key_gen' );
		wp_clear_scheduled_hook( 'wpdef_clear_scan_logs' );
		wp_clear_scheduled_hook( 'wpdef_log_rotational_delete' );
		wp_clear_scheduled_hook( 'wpdef_update_geoip' );
		wp_clear_scheduled_hook( 'wpdef_fetch_global_ip_list' );
		wp_clear_scheduled_hook( 'wpdef_quarantine_delete_expired' );
		wp_clear_scheduled_hook( 'wpdef_firewall_clean_up_lockout' );
		wp_clear_scheduled_hook( 'wpdef_firewall_send_compact_logs_to_api' );
		wp_clear_scheduled_hook( 'wpdef_firewall_fetch_trusted_proxy_preset_ips' );
		wp_clear_scheduled_hook( 'wpdef_firewall_clean_up_unlockout' );
		wp_clear_scheduled_hook( 'wpdef_antibot_global_firewall_fetch_blocklist' );
		wp_clear_scheduled_hook( 'wpdef_smart_ip_detection_ping' );
		wp_clear_scheduled_hook( 'wpdef_confirm_antibot_toggle_on_hosting' );
		wp_clear_scheduled_hook( 'wpdef_firewall_whitelist_server_public_ip' );
		wp_clear_scheduled_hook( 'wpdef_rotate_malicious_bot_secret_hash' );

		// Remove old legacy cron jobs if they exist.
		wp_clear_scheduled_hook( 'lockoutReportCron' );
		wp_clear_scheduled_hook( 'auditReportCron' );
		wp_clear_scheduled_hook( 'cleanUpOldLog' );
		wp_clear_scheduled_hook( 'scanReportCron' );
		wp_clear_scheduled_hook( 'tweaksSendNotification' );
	}

	/**
	 * Creates the 'defender_unlockout' table if it doesn't exist in the database.
	 *
	 * @return void
	 */
	public function create_table_unlockout() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = <<<SQL
		CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_unlockout (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ip` varchar(45) DEFAULT NULL,
			`type` varchar(16) NOT NULL,
			`email` varchar(255) NOT NULL,
			`status` varchar(16) NOT NULL,
			`timestamp` int(11) NOT NULL,
			PRIMARY KEY  (`id`),
			KEY `ip` (`ip`),
			KEY `type` (`type`),
			KEY `email` (`email`),
			KEY `status` (`status`)
		   ) {$charset_collate};
SQL;
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Create blocklist table.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	public function create_table_blocklist(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = <<<SQL
		CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_antibot (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ip` varchar(45) NOT NULL,
			`unlocked` tinyint(1) DEFAULT NULL,
			`unlocked_at` int(11) DEFAULT NULL,
			PRIMARY KEY  (`id`),
			UNIQUE KEY ip (ip)
		   ) {$charset_collate};
SQL;
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Creates Defender's tables.
	 *
	 * @since 2.7.1 No use dbDelta because PHP v8.1 triggers an error when calling query "DESCRIBE {$table};" if the
	 *     table doesn't exist.
	 */
	protected function create_database_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		// Hide errors.
		$wpdb->hide_errors();
		// Email log table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_email_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `source` varchar(255) NOT NULL,
 `to` varchar(255) NOT NULL,
 PRIMARY KEY  (`id`),
 KEY `source` (`source`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Audit log table. Though our data mainly store on API side, we will need a table for caching.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_audit_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `event_type` varchar(255) NOT NULL,
 `action_type` varchar(255) NOT NULL,
 `site_url` varchar(255) NOT NULL,
 `user_id` int NOT NULL,
 `context` varchar(255) NOT NULL,
 `ip` varchar(45) NOT NULL,
 `msg` varchar(255) NOT NULL,
 `blog_id` int NOT NULL,
 `synced` int NOT NULL,
 `ttl` int NOT NULL,
 PRIMARY KEY  (`id`),
 KEY `event_type` (`event_type`),
 KEY `action_type` (`action_type`),
 KEY `user_id` (`user_id`),
 KEY `context` (`context`),
 KEY `ip` (`ip`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Scan item table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_scan_item (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `parent_id` int NOT NULL,
 `type` varchar(255) NOT NULL,
 `status` varchar(255) NOT NULL,
 `raw_data` text NOT NULL,
 PRIMARY KEY  (`id`),
 KEY `type` (`type`),
 KEY `status` (`status`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Scan table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_scan (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `percent` float NOT NULL,
 `total_tasks` tinyint(4) NOT NULL,
 `task_checkpoint` varchar(255) NOT NULL,
 `status` varchar(255) NOT NULL,
 `date_start` datetime NOT NULL,
 `date_end` datetime NOT NULL,
 `is_automation` bool NOT NULL,
 PRIMARY KEY  (`id`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Lockout log table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_lockout_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `log` text,
 `ip` varchar(45) DEFAULT NULL,
 `date` int(11) DEFAULT NULL,
 `type` varchar(16) DEFAULT NULL,
 `user_agent` varchar(255) DEFAULT NULL,
 `blog_id` int(11) DEFAULT NULL,
 `tried` varchar(255),
 `country_iso_code` char(2) DEFAULT NULL,
 PRIMARY KEY  (`id`),
 KEY `ip` (`ip`),
 KEY `type` (`type`),
 KEY `tried` (`tried`),
 KEY `country_iso_code` (`country_iso_code`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Lockout table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_lockout (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `ip` varchar(45) DEFAULT NULL,
 `status` varchar(16) DEFAULT NULL,
 `lockout_message` text,
 `release_time` int(11) DEFAULT NULL,
 `lock_time` int(11) DEFAULT NULL,
 `lock_time_404` int(11) DEFAULT NULL,
 `attempt` int(11) DEFAULT NULL,
 `attempt_404` int(11) DEFAULT NULL,
 `meta` text,
 PRIMARY KEY  (`id`),
 KEY `ip` (`ip`),
 KEY `status` (`status`),
 KEY `attempt` (`attempt`),
 KEY `attempt_404` (`attempt_404`)
) $charset_collate;";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->create_table_quarantine();

		// Create Unlock table.
		$this->create_table_unlockout();
		// Create Blocklist table.
		$this->create_table_blocklist();
	}

	/**
	 * Check if this is onboarding.
	 *
	 * @return bool
	 */
	private function is_onboarding(): bool {
		/**
		 * Display Onboarding if:
		 * it's a fresh install and there were no requests from the Hub before,
		 * after Reset Settings.
		 *
		 * @var HUB
		 */
		$hub_class = wd_di()->get( HUB::class );
		$hub_class->set_onboarding_status( Onboard_Model::maybe_show_onboarding() );

		return $hub_class->get_onboarding_status() && ! defender_is_wp_cli();
	}

	/**
	 * Initialize the common modules of the application.
	 *
	 * @return void
	 */
	private function init_modules_common(): void {
		// Init main ORM.
		Array_Cache::set( 'orm', new Mapper() );

		if ( $this->is_onboarding() ) {
			// If it's cli we should start this normally.
			Array_Cache::set( 'onboard', wd_di()->get( Onboard::class ) );
		} else {
			// Initialize the main controllers of every module.
			wd_di()->get( Dashboard::class );
		}
		wd_di()->get( Security_Tweaks::class );
		wd_di()->get( Scan::class );
		wd_di()->get( Audit_Logging::class );
		wd_di()->get( Firewall_Controller::class );
		wd_di()->get( WAF::class );
		wd_di()->get( Two_Factor::class );
		wd_di()->get( Advanced_Tools::class );
		wd_di()->get( Mask_Login::class );
		wd_di()->get( Security_Headers::class );
		wd_di()->get( Recaptcha::class );
		wd_di()->get( Notification::class );
		wd_di()->get( Main_Setting::class );
		wd_di()->get( Blocklist_Monitor::class );
		wd_di()->get( Password_Protection::class );
		wd_di()->get( Password_Reset::class );
		wd_di()->get( Webauthn::class );
		wd_di()->get( Expert_Services::class );
		wd_di()->get( Hub_Connector_Controller::class );
		wd_di()->get( Strong_Password::class );
		wd_di()->get( Session_Protection::class );

		if ( class_exists( 'WP_Defender\Controller\Quarantine' ) ) {
			wd_di()->get( Quarantine::class );
		}
		wd_di()->get( Data_Tracking::class );
		if ( defender_is_wp_org_version() ) {
			wd_di()->get( Rate_Controller::class );
		}

		if ( is_multisite() ) {
			wd_di()->get( Network_Cron_Manager::class );
		}
	}

	/**
	 * Adds a specific class to the body tag if the current page is a Defender page.
	 *
	 * @param  string $classes  The existing body classes.
	 *
	 * @return string The modified body classes.
	 */
	public function add_sui_to_body( $classes ) {
		if ( ! is_defender_page() ) {
			return $classes;
		}
		$classes .= sprintf( ' sui-%s ', DEFENDER_SUI );

		return $classes;
	}

	/**
	 * Registers the necessary styles for the plugin.
	 *
	 * @return void
	 */
	private function register_styles(): void {
		wp_enqueue_style( 'defender-menu', WP_DEFENDER_BASE_URL . 'assets/css/defender-icon.css', array(), DEFENDER_VERSION );

		$css_files = array(
			'defender' => WP_DEFENDER_BASE_URL . 'assets/css/styles.css',
		);

		foreach ( $css_files as $slug => $file ) {
			wp_register_style( $slug, $file, array(), DEFENDER_VERSION );
		}
	}

	/**
	 * Registers the necessary scripts for the plugin.
	 *
	 * @return void
	 */
	private function register_scripts(): void {
		$base_url     = WP_DEFENDER_BASE_URL;
		$dependencies = array( 'def-vue', 'def-manifest', 'defender', 'wp-i18n' );
		$js_files     = array(
			'wpmudev-sui'         => array(
				$base_url . 'assets/js/shared-ui.js',
			),
			'defender'            => array(
				$base_url . 'assets/js/scripts.js',
			),
			'def-vue'             => array(
				$base_url . 'assets/js/vendor.js',
			),
			'def-manifest'        => array(
				$base_url . 'assets/js/manifest.js',
			),
			'def-dashboard'       => array(
				$base_url . 'assets/app/dashboard.js',
				$dependencies,
			),
			'def-securitytweaks'  => array(
				$base_url . 'assets/app/security-tweak.js',
				array_merge( $dependencies, array( 'clipboard', 'wpmudev-sui' ) ),
			),
			'def-scan'            => array(
				$base_url . 'assets/app/scan.js',
				array_merge( $dependencies, array( 'clipboard', 'wpmudev-sui' ) ),
			),
			'def-audit'           => array(
				$base_url . 'assets/app/audit.js',
				$dependencies,
			),
			'def-iplockout'       => array(
				$base_url . 'assets/app/ip-lockout.js',
				array_merge( $dependencies, array( 'wpmudev-sui' ) ),
			),
			'def-advancedtools'   => array(
				$base_url . 'assets/app/advanced-tools.js',
				$dependencies,
			),
			'def-settings'        => array(
				$base_url . 'assets/app/settings.js',
				$dependencies,
			),
			'def-2fa'             => array(
				$base_url . 'assets/app/two-fa.js',
				$dependencies,
			),
			'def-notification'    => array(
				$base_url . 'assets/app/notification.js',
				$dependencies,
			),
			'def-waf'             => array(
				$base_url . 'assets/app/waf.js',
				$dependencies,
			),
			'def-onboard'         => array(
				$base_url . 'assets/app/onboard.js',
				$dependencies,
			),
			'def-expert-services' => array(
				$base_url . '/assets/app/expert-services.js',
				$dependencies,
			),
		);

		foreach ( $js_files as $slug => $file ) {
			if ( isset( $file[1] ) ) {
				wp_register_script( $slug, $file[0], $file[1], DEFENDER_VERSION, true );
				wp_set_script_translations( $slug, 'wpdef' );
			} else {
				wp_register_script( $slug, $file[0], array( 'jquery' ), DEFENDER_VERSION, true );
			}
		}
	}

	/**
	 * Localizes the script by adding necessary data to the 'defender' object.
	 *
	 * @return void
	 */
	private function localize_script(): void {
		$wpmu_dev = new WPMUDEV();
		global $wp_defender_central;

		$misc          = array();
		$data_tracking = wd_di()->get( Data_Tracking::class );
		$is_tracking   = $data_tracking->show_tracking_modal();
		if ( $is_tracking ) {
			$misc = $data_tracking->get_tracking_modal();
		}
		$misc['high_contrast'] = defender_high_contrast();

		if ( defender_is_wp_org_version() ) {
			$misc['rating'] = array();
			$rate_service   = Rate_Component::is_achievement_displayed();
			if ( $rate_service['is_displayed'] ) {
				$misc['rating']         = wd_di()->get( Rate_Controller::class )->data_frontend();
				$misc['rating']['text'] = Rate_Component::get_notice_by_slug( $rate_service['slug'] );
			}

			$misc['rating']['is_displayed'] = $rate_service['is_displayed'];
			$misc['rating']['type']         = $rate_service['slug'];
		} else {
			$misc['rating']['is_displayed'] = false;
		}

		wp_localize_script(
			'def-vue',
			'defender',
			array(
				'whitelabel'                  => defender_white_label_status(),
				'misc'                        => $misc,
				'home_url'                    => network_home_url(),
				'site_url'                    => network_site_url(),
				'admin_url'                   => network_admin_url(),
				'defender_url'                => WP_DEFENDER_BASE_URL,
				'is_free'                     => $wpmu_dev->is_pro() ? 0 : 1,
				'is_membership'               => true,
				'is_whitelabel'               => $wpmu_dev->is_whitelabel_enabled() ? 'enabled' : 'disabled',
				'wpmu_dev_url_action'         => $wpmu_dev->hide_wpmu_dev_urls() ? 'hide' : 'show',
				'opcache_save_comments'       => $wp_defender_central->is_opcache_save_comments_disabled() ? 'disabled' : 'enabled',
				'opcache_message'             => $wp_defender_central->display_opcache_message(),
				'wpmudev_url'                 => WP_DEFENDER_DOCS_LINK,
				'wpmudev_support_ticket_text' => defender_support_ticket_text(),
				'wpmudev_api_base_url'        => $wpmu_dev->get_api_base_url(),
				'upgrade_title'               => esc_html__( 'UPGRADE TO PRO', 'wpdef' ),
				'tracking_modal'              => $is_tracking ? 'show' : 'hide',
				'hosted'                      => $wpmu_dev->is_wpmu_hosting(),
				'file_upload_nonce'           => wp_create_nonce( 'defender_file_upload' ),
				'wpmudev_hub_link'            => 'https://wpmudev.com/hub2/',
			)
		);

		wp_localize_script( 'defender', 'defenderGetText', defender_gettext_translations() );
	}

	/**
	 * Register all core assets.
	 */
	public function register_assets(): void {
		$this->register_styles();
		$this->register_scripts();
		$this->localize_script();

		do_action( 'defender_enqueue_assets' );
	}

	/**
	 * Trigger mandatory actions on activation.
	 */
	private function on_activation(): void {
		add_action(
			'admin_init',
			function () {
				$security_tweaks = wd_di()->get( Security_Tweaks::class );
				$security_tweaks->get_security_key()->cron_schedule();
			}
		);
	}

	/**
	 * Returns the cron schedules.
	 *
	 * @param  array $schedules  The existing cron schedules.
	 *
	 * @return array The updated cron schedules.
	 */
	public function cron_schedules( array $schedules ) {
		return defender_cron_schedules( $schedules );
	}

	/**
	 * Initialize the modules and register the plugin routes. Also include the admin class, adds WP-CLI commands.
	 *
	 * @return void
	 */
	public function includes(): void {
		// Initialize modules.
		add_action(
			'after_setup_theme',
			function () {
				add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
				$this->init_modules();
			}
		);
		// Register routes.
		add_action(
			'init',
			function () {
				require_once WP_DEFENDER_DIR . 'src/routes.php';
			},
			9
		);
		// Register the Hub Connector early to handle the auth callback during the admin init hook.
		add_action( 'plugins_loaded', array( wd_di()->get( Hub_Connector::class ), 'init' ) );
		// Register the Cross-Sell module.
		add_action( 'init', array( wd_di()->get( \WP_Defender\Component\Cross_Sell::class ), 'init' ), 9 );
		// Include admin class. Don't use is_admin().
		add_action( 'admin_init', array( ( new Admin() ), 'init' ) );
		// Add WP-CLI commands.
		if ( defender_is_wp_cli() ) {
			WP_CLI::add_command( 'defender', Cli::class );
		}
		// Rotational logger initialization.
		add_action( 'init', array( ( new Rotation_Logger() ), 'init' ), 99 );
		// Handle plugin deactivation.
		add_action( 'deactivated_plugin', array( ( new HUB() ), 'intercept_deactivate' ) );
	}
}