<?php

namespace WP_Defender;

use Calotes\DB\Mapper;
use Calotes\Helper\Array_Cache;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Cli;
use WP_Defender\Controller\Advanced_Tools;
use WP_Defender\Controller\Dashboard;
use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Controller\Firewall;
use WP_Defender\Controller\HUB;
use WP_Defender\Controller\Mask_Login;
use WP_Defender\Controller\Notification;
use WP_Defender\Controller\Onboard;
use WP_Defender\Controller\Password_Protection;
use WP_Defender\Controller\Recaptcha;
use WP_Defender\Controller\Scan;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Controller\Main_Setting;
use WP_Defender\Controller\WAF;
use WP_Defender\Controller\Tutorial;
use WP_Defender\Controller\Blocklist_Monitor;
use WP_Defender\Controller\Password_Reset;

/**
 * Class Bootstrap
 * @package WP_Defender
 */
class Bootstrap {
	/**
	 * Activation.
	 */
	public function activation_hook() {
		$this->create_database_tables();
		$this->set_free_installation_timestamp();
		$this->on_activation();
	}

	/**
	 * Deactivation.
	 */
	public function deactivation_hook() {
		wp_clear_scheduled_hook( 'firewall_clean_up_logs' );
		wp_clear_scheduled_hook( 'wdf_maybe_send_report' );
		wp_clear_scheduled_hook( 'wp_defender_clear_logs' );
		wp_clear_scheduled_hook( 'wpdef_sec_key_gen' );
		wp_clear_scheduled_hook( 'wpdef_clear_scan_logs' );

		// Remove old legacy cron jobs if they exist.
		wp_clear_scheduled_hook( 'lockoutReportCron' );
		wp_clear_scheduled_hook( 'auditReportCron' );
		wp_clear_scheduled_hook( 'cleanUpOldLog' );
		wp_clear_scheduled_hook( 'scanReportCron' );
		wp_clear_scheduled_hook( 'tweaksSendNotification' );
	}

	/**
	 * Creates Defender tables.
	 */
	protected function create_database_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$wpdb->hide_errors();
		// Email log table.
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_email_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `source` varchar(255) NOT NULL,
 `to` varchar (255) NOT NULL,
 PRIMARY KEY  (`id`),
 KEY `source` (`source`)
) $charset_collate;";
		dbDelta( $sql );

		/**
		 * Though our data mainly store on API side, we will need a table for caching.
		 */
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_audit_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `event_type` varchar(255) NOT NULL,
 `action_type` varchar (255) NOT NULL,
 `site_url` varchar (255) NOT NULL,
 `user_id` int NOT NULL,
 `context` varchar (255) NOT NULL,
 `ip` varchar (255) NOT NULL,
 `msg` varchar (255) NOT NULL,
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
		dbDelta( $sql );

		// Scan item table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_scan_item (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `parent_id` int NOT NULL,
 `type` varchar(255) NOT NULL,
 `status` varchar (255) NOT NULL,
 `raw_data` text NOT NULL,
 PRIMARY KEY  (`id`),
 KEY `type` (`type`),
 KEY `status` (`status`)
) $charset_collate;";
		dbDelta( $sql );

		// Scan table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_scan (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `percent` float NOT NULL,
 `total_tasks` tinyint(4) NOT NULL,
 `task_checkpoint` varchar(255) NOT NULL,
 `status` varchar(255) NOT NULL,
 `date_start` datetime NOT NULL,
 `date_end` datetime NOT NULL,
 `is_automation` Bool NOT NULL,
 PRIMARY KEY  (`id`)
) $charset_collate;";
		dbDelta( $sql );

		// Lockout log table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_lockout_log (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `log` text,
 `ip` varchar(255) DEFAULT NULL,
 `date` int(11) DEFAULT NULL,
 `type` varchar(16) DEFAULT NULL,
 `user_agent` varchar(255) DEFAULT NULL,
 `blog_id` int(11) DEFAULT NULL,
 `tried` VARCHAR (255),
 PRIMARY KEY  (`id`),
 KEY `ip` (`ip`),
 KEY `type` (`type`),
 KEY `tried` (`tried`)
) $charset_collate;";
		dbDelta( $sql );

		// Lockout table.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}defender_lockout (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `ip` varchar(255) DEFAULT NULL,
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
		dbDelta( $sql );
	}

	/**
	 * Add option with plugin install date.
	 *
	 * @since 2.4
	 */
	protected function set_free_installation_timestamp() {
		// It's for both cases because don’t have a Pro checking during plugin activation.
		if ( empty( get_site_option( 'defender_free_install_date' ) ) ) {
			update_site_option( 'defender_free_install_date', time() );
		}
	}

	/**
	 * Load all modules.
	 */
	public function init_modules() {
		// Init main ORM.
		Array_Cache::set( 'orm', new Mapper() );
		/**
		 * Display Onboarding if:
		 * it's a fresh install and there were no requests from the Hub before,
		 * after Reset Settings.
		*/
		$hub_class = wd_di()->get( HUB::class );
		$hub_class->set_onboarding_status( $this->maybe_show_onboarding() );
		$hub_class->listen_to_requests();
		if ( $hub_class->get_onboarding_status() && 'cli' !== php_sapi_name() ) {
			// If it's cli we should start this normally.
			Array_Cache::set( 'onboard', wd_di()->get( Onboard::class ) );
		} else {
			// Initialize the main controllers of every module.
			wd_di()->get( Dashboard::class );
		}
		wd_di()->get( Security_Tweaks::class );
		wd_di()->get( Scan::class );
		wd_di()->get( Audit_Logging::class );
		wd_di()->get( Firewall::class );
		wd_di()->get( WAF::class );
		wd_di()->get( Two_Factor::class );
		wd_di()->get( Advanced_Tools::class );
		wd_di()->get( Mask_Login::class );
		wd_di()->get( Security_Headers::class );
		wd_di()->get( Recaptcha::class );
		wd_di()->get( Notification::class );
		wd_di()->get( Main_Setting::class );
		wd_di()->get( Tutorial::class );
		wd_di()->get( Blocklist_Monitor::class );
		wd_di()->get( Password_Protection::class );
		wd_di()->get( Password_Reset::class );
		$this->init_free_dashboard();
	}

	/**
	 * @return bool
	 */
	private function maybe_show_onboarding() {
		// First we need to check if the site is newly create.
		global $wpdb;
		if ( ! is_multisite() ) {
			$res = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'wp_defender_shown_activator'" );
		} else {
			$sql = $wpdb->prepare(
				"SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = 'wp_defender_shown_activator' AND site_id = %d",
				get_current_network_id()
			);
			$res = $wpdb->get_var( $sql );
		}
		// Get '1' for direct SQL request if Onboarding was already.
		if ( empty( $res ) ) {
			return true;
		}

		return false;
	}

	public function init_free_dashboard() {
		require_once defender_path( 'extra/free-dashboard/module.php' );
		add_filter( 'wdev-email-message-' . DEFENDER_PLUGIN_BASENAME, array( &$this, 'defender_ads_message' ) );
		do_action(
			'wdev-register-plugin',
			/* 1             Plugin ID */
			DEFENDER_PLUGIN_BASENAME,
			'Defender',
			'/plugins/defender-security/',
			/* 4      Email Button CTA */
			__( 'Get Secure!', 'wpdef' ),
			/* 5  getdrip Plugin param */
			'0cecf2890e'
		);
	}

	/**
	 * @param $message
	 *
	 * @return string|void
	 */
	public function defender_ads_message( $message ) {
		return __( "You're awesome for installing Defender! Are you interested in how to make the most of this plugin? We've collected all the best security resources we know in a single email - just for users of Defender!", 'wpdef' );
	}

	public function init_cli_command() {
		\WP_CLI::add_command( 'defender', Cli::class );
	}

	public function add_sui_to_body( $classes ) {
		if ( ! defender_current_page() ) {

			return $classes;
		}
		$classes .= sprintf( ' sui-%s ', DEFENDER_SUI );

		return $classes;
	}

	/**
	 * Register all core assets.
	 */
	public function register_assets() {
		$base_url = plugin_dir_url( __DIR__ );
		wp_enqueue_style( 'defender-menu', $base_url . 'assets/css/defender-icon.css' );

		$css_files = array(
			'defender' => $base_url . 'assets/css/styles.css',
		);

		foreach ( $css_files as $slug => $file ) {
			wp_register_style( $slug, $file, array(), DEFENDER_VERSION );
		}
		$is_min       = defined( 'SCRIPT_DEBUG' ) && constant( 'SCRIPT_DEBUG' ) === true ? '' : '.min';
		$dependencies = [
			'def-vue',
			'defender',
			'wp-i18n'
		];
		$js_files     = array(
			'wpmudev-sui'        => [
				$base_url . 'assets/js/shared-ui.js',
			],
			'defender'           => [
				$base_url . 'assets/js/scripts.js',
			],
			'def-vue'            => [
				$base_url . 'assets/js/vendor/vue.runtime' . $is_min . '.js'
			],
			'def-dashboard'      => [
				$base_url . 'assets/app/dashboard.js',
				$dependencies,
			],
			'def-securitytweaks' => [
				$base_url . 'assets/app/security-tweak.js',
				array_merge( $dependencies, [ 'clipboard', 'wpmudev-sui' ] ),
			],
			'def-scan'           => [
				$base_url . 'assets/app/scan.js',
				$dependencies,
			],
			'def-audit'          => [
				$base_url . 'assets/app/audit.js',
				$dependencies,
			],
			'def-iplockout'      => [
				$base_url . 'assets/app/ip-lockout.js',
				$dependencies,
			],
			'def-advancedtools'  => [
				$base_url . 'assets/app/advanced-tools.js',
				$dependencies,
			],
			'def-settings'       => [
				$base_url . 'assets/app/settings.js',
				$dependencies,
			],
			'def-2fa'            => [
				$base_url . 'assets/app/two-fa.js',
				$dependencies
			],
			'def-notification'   => [
				$base_url . 'assets/app/notification.js',
				$dependencies
			],
			'def-waf'            => [
				$base_url . 'assets/app/waf.js',
				$dependencies
			],
			'def-onboard'        => [
				$base_url . 'assets/app/onboard.js',
				$dependencies
			],
			'def-tutorial'       => [
				$base_url . 'assets/app/tutorial.js',
				$dependencies
			]
		);

		global $wp_defender_central;

		foreach ( $js_files as $slug => $file ) {
			if ( isset( $file[1] ) ) {
				wp_register_script( $slug, $file[0], $file[1], DEFENDER_VERSION, true );
			} else {
				wp_register_script( $slug, $file[0], array( 'jquery' ), DEFENDER_VERSION, true );
			}
		}

		$wpmu_dev = new WPMUDEV();

		wp_localize_script( 'def-vue', 'defender', [
			'whitelabel'            => defender_white_label_status(),
			'misc'                  => [
				'high_contrast' => $wpmu_dev->maybe_high_contrast(),
			],
			'site_url'              => network_site_url(),
			'admin_url'             => network_admin_url(),
			'defender_url'          => $base_url,
			'is_free'               => $wpmu_dev->is_pro() ? 0 : 1,
			'is_membership'         => true,
			'is_whitelabel'         => $wpmu_dev->is_whitelabel_enabled() ? 'enabled' : 'disabled',
			'opcache_save_comments' => $wp_defender_central->is_opcache_save_comments_disabled() ? 'disabled' : 'enabled',
			'wpmudev_url'           => 'https://wpmudev.com/docs/wpmu-dev-plugins/defender/',
		] );

		wp_localize_script( 'defender', 'defenderGetText', $this->defender_gettext_translations() );

		do_action( 'defender_enqueue_assets' );
	}

	/**
	 * Check to exist table.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	private function table_exists( $table_name ) {
		global $wpdb;
		// Full table name.
		$table_name = $wpdb->base_prefix . $table_name;

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Check and create tables if its aren't existed.
	 */
	public function check_if_table_exists() {
		$db_version = get_site_option( 'wd_db_version' );
		if ( isset( $db_version ) && version_compare( $db_version, '2.4', '>=') ) {

			return;
		}

		if (
			! $this->table_exists( 'defender_lockout' )
			|| ! $this->table_exists( 'defender_lockout_log' )
			|| ! $this->table_exists( 'defender_scan' )
			|| ! $this->table_exists( 'defender_scan_item' )
			|| ! $this->table_exists( 'defender_audit_log' )
			|| ! $this->table_exists( 'defender_email_log' )
		) {
			$this->create_database_tables();
		}
	}

	/**
	 * Find all the strings from .mo file.
	 * `wpdef` is our text domain.
	 */
	private function defender_gettext_translations() {
		global $l10n;

		if ( ! isset( $l10n['wpdef'] ) ) {
			return array();
		}

		$items = array();

		foreach ( $l10n['wpdef']->entries as $key => $value ) {
			$items[ $key ] = count( $value->translations ) ? $value->translations[0] : $key;
		}

		return $items;
	}

	/**
	 * Trigger mandatory actions on activation.
	 */
	private function on_activation() {
		add_action(
			'admin_init',
			function() {
				$security_tweaks = wd_di()->get( Security_Tweaks::class );
				$security_tweaks->get_security_key()->cron_schedule();
			}
		);
	}
}
