<?php
/**
 * UI class handling all the output, also modifying all the output to represent
 * WPMU DEV brand such as WP Admin pages, notifications and so on.
 *
 * @package WPMU DEV Dashboard
 */

/**
 * UI class handling all the output, also modifying all the output to represent
 * WPMU DEV brand such as WP Admin pages, notifications and so on.
 */
class WPMUDEV_Dashboard_Ui {

	/**
	 * An object that defines all the URLs for the Dashboard menu/submenu items.
	 *
	 * @var WPMUDEV_Dashboard_Sui_Page_Urls
	 */
	public $page_urls = null;

	/**
	 * The noticeid of the membership
	 *
	 * @var int (Task ID's last 4 digits)
	 */
	protected $_membership_notice = 9874;

	/**
	 * Set up the UI module. This adds all the initial hooks for the plugin
	 *
	 * @internal
	 */
	public function __construct() {
		// Redirect to login screen on first plugin activation.
		add_action( 'load-plugins.php', array( $this, 'login_redirect' ) );

		// Localize the plugin.
		add_action( 'plugins_loaded', array( $this, 'localization' ) );

		// Hook up our WordPress customizations.
		add_action( 'init', array( $this, 'setup_branding' ) );

		$this->page_urls = new WPMUDEV_Dashboard_Sui_Page_Urls();

		add_filter(
			'wp_prepare_themes_for_js',
			array( $this, 'hide_upfront_theme' ),
			100
		);

		add_action(
			'load-plugins.php',
			array( $this, 'brand_updates_table' ),
			21 // Must be called after WP which is 20.
		);
		add_action(
			'load-themes.php',
			array( $this, 'brand_updates_table' ),
			21 // Must be called after WP which is 20.
		);

		// Changelog modal.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_changelog_assets' ) );
		add_action( 'admin_footer', array( $this, 'changelog_modal' ) );

		// Some core updates need to be modified via javascript.
		add_action(
			'core_upgrade_preamble',
			array( $this, 'modify_core_updates_page' )
		);

		add_action(
			'all_plugins',
			array( $this, 'remove_dashboard_when_whitelabeled' )
		);

		// Analytics.
		add_action( 'wp_dashboard_setup', array( $this, 'analytics_widget_setup' ) );
		add_action( 'wp_network_dashboard_setup', array( $this, 'analytics_widget_setup' ) );

		// Render upgrade highlights modal.
		add_action(
			'wpmudev_dashboard_ui_before_footer',
			array( $this, 'render_highlights_modal' )
		);

		/**
		 * Run custom initialization code for the UI module.
		 *
		 * @var  WPMUDEV_Dashboard_Ui The dashboards UI module.
		 * @since  4.0.0
		 */
		do_action( 'wpmudev_dashboard_ui_init', $this );
	}

	/**
	 * Removes WPMU DEV Dashboard from native plugins page when
	 * whitelabeling is enabled.
	 *
	 * @param array $all_plugins - list of installed plugins.
	 *
	 * @return array - list of plugins with Dash removed if needed.
	 */
	public function remove_dashboard_when_whitelabeled( $all_plugins ) {
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		if ( $whitelabel_settings['enabled'] ) {
			unset( $all_plugins['wpmudev-updates/update-notifications.php'] );
		}

		return $all_plugins;
	}

	/**
	 * Checks if plugin was just activated, and redirects to login page.
	 * No redirect if plugin was activated via bulk-update.
	 *
	 * @internal Action hook
	 * @since    1.0.0
	 */
	public function login_redirect() {

		// We only redirect right after plugin activation.
		if ( ( empty( $_GET['activate'] ) || 'true' !== $_GET['activate'] ) || ! empty( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			$redirect = false;
		} elseif ( WPMUDEV_Dashboard::$api->has_key() ) {
			$redirect = false;
		} else {
			$redirect = true; // this means we are on the right page and not logged in.
		}

		if ( $redirect ) {
			// This is not a valid request.
			if ( defined( 'DOING_AJAX' ) ) {
				$redirect = false;
			} elseif ( ! current_user_can( 'install_plugins' ) ) {
				// User is not allowed to login to the dashboard.
				$redirect = false;
			} elseif ( WPMUDEV_Dashboard::$site->get_option( 'redirected_v4' ) ) {
				// We already redirected the user to login page before.
				$redirect = false;
			}
		}

		/* ----- Save the flag and redirect if needed ----- */
		if ( $redirect ) {
			WPMUDEV_Dashboard::$site->set_option( 'redirected_v4', 1 );

			// Force refresh of all data during first redirect.
			WPMUDEV_Dashboard::$site->set_option( 'refresh_remote_flag', 1 );
			WPMUDEV_Dashboard::$site->set_option( 'refresh_profile_flag', 1 );

			header( 'X-Redirect-From: UI first_redirect' );
			wp_safe_redirect( $this->page_urls->dashboard_url );
			exit;
		}
	}

	/**
	 * Load the translations if WordPress uses non-english language.
	 *
	 * For this you need a ".mo" file with translations.
	 * Name the file "wpmudev-[value in wp-config].mo"  (e.g. wpmudev-de_De.mo)
	 * Save the file to the folder "wp-content/languages/plugins/"
	 *
	 * @internal Action hook
	 * @since    1.0.0
	 */
	public function localization() {
		load_plugin_textdomain(
			'wpmudev',
			false,
			WPMUDEV_Dashboard::$site->plugin_dir . '/language/'
		);
	}

	/**
	 * Register our plugin branding.
	 *
	 * I.e. Setup all the things that are NOT on the dashboard page but modify
	 * the look & feel of WordPress core pages.
	 *
	 * @internal Action hook
	 * @since    1.0.0
	 */
	public function setup_branding() {
		/*
		 * If the current user has access to the WPMUDEV Dashboard then we
		 * always set up our branding hooks.
		 */
		if ( ! WPMUDEV_Dashboard::$site->allowed_user() ) {
			return false;
		}

		// Always add this toolbar item, also on front-end.
		add_action(
			'admin_bar_menu',
			array( $this, 'setup_toolbar' ),
			999
		);

		if ( ! is_admin() ) {
			return false;
		}

		// Add branded links to install/update process.
		add_filter(
			'install_plugin_complete_actions',
			array( $this, 'branding_install_plugin_done' ),
			10,
			3
		);
		add_filter(
			'update_plugin_complete_actions',
			array( $this, 'branding_update_plugin_done' ),
			10,
			2
		);

		// Add the menu icon to the admin menu.
		if ( is_multisite() ) {
			$menu_hook = 'network_admin_menu';
		} else {
			$menu_hook = 'admin_menu';
		}

		add_action(
			$menu_hook,
			array( $this, 'setup_menu' )
		);

		// Abort request if we only need the menu.
		add_action(
			'in_admin_header',
			array( $this, 'maybe_return_menu' )
		);

		// Always load notification css.
		add_action(
			'admin_print_styles',
			array( $this, 'notification_styles' )
		);
	}

	/**
	 * Removes Upfront from being activatable in the theme browser.
	 *
	 * @param array $prepared_themes List of installed WordPress themes.
	 *
	 * @internal Action hook
	 *
	 * @since    3.0.0
	 * @return array
	 */
	public function hide_upfront_theme( $prepared_themes ) {
		unset( $prepared_themes['upfront'] );

		return $prepared_themes;
	}

	/**
	 * Here we will set up custom code to display WPMUDEV plugins/themes on the
	 * pages for WP Updates, Themes and Plugins.
	 *
	 * @since  4.0.0
	 */
	public function brand_updates_table() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// don't show on per site plugins list, just like core.
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
		if ( is_array( $updates ) && count( $updates ) ) {
			foreach ( $updates as $item ) {
				if ( ! empty( $item['autoupdate'] ) && 2 !== $item['autoupdate'] ) {
					if ( 'theme' === $item['type'] ) {
						$hook = 'after_theme_row_' . dirname( $item['filename'] );
					} else {
						$hook = 'after_plugin_row_' . $item['filename'];
					}
					remove_all_actions( $hook );
					add_action( $hook, array( $this, 'brand_updates_plugin_row' ), 9, 2 );
				}
			}
		}

		$id_themepack = WPMUDEV_Dashboard::$site->id_farm133_themes;
		if ( isset( $updates[ $id_themepack ] ) ) {
			$update    = $updates[ $id_themepack ];
			$themepack = WPMUDEV_Dashboard::$site->get_farm133_themepack();
			if ( is_array( $themepack ) && count( $themepack ) ) {
				foreach ( $themepack as $item ) {
					$hook = 'after_theme_row_' . $item['filename'];
					remove_all_actions( $hook );

					// Only add the notice if specific version is wrong.
					if ( version_compare( $item['version'], $update['new_version'], '<' ) ) {
						add_action( $hook, array( $this, 'brand_updates_farm133_row' ), 9, 2 );
					}
				}
			}
		}
	}

	/**
	 * Print changelog modal template.
	 *
	 * This is required only on plugins list page.
	 *
	 * @since 4.11
	 *
	 * @return void
	 */
	public function changelog_modal() {
		// Only if current user can update plugins.
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Don't show on per site plugins list, just like core.
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		$screen = get_current_screen();

		// Screen ID should be available.
		if ( $screen && is_object( $screen ) ) {
			// Only if updates page.
			if ( in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
				// Get available updates.
				$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );

				if ( ! empty( $updates ) ) {
					// Enqueue assets.
					wp_enqueue_style( 'wpmudev-dashboard-changelog' );
					wp_enqueue_script( 'wpmudev-dashboard-changelog' );
					// Localized vars.
					wp_localize_script(
						'wpmudev-dashboard-changelog',
						'wpmudevDashboard',
						array( 'nonce' => wp_create_nonce( 'show-popup' ) )
					);

					// Render template.
					$this->render( 'sui/popup-changelog' );
				}
			}
		}
	}

	/**
	 * Register scripts and styles for changelog modal.
	 *
	 * @since 4.11
	 *
	 * @return void
	 */
	public function register_changelog_assets() {
		wp_register_style(
			'wpmudev-dashboard-changelog',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/css/dashboard-changelog.min.css',
			array(),
			WPMUDEV_Dashboard::$version
		);

		wp_register_script(
			'wpmudev-dashboard-changelog',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-changelog.min.js',
			array( 'jquery' ),
			WPMUDEV_Dashboard::$version,
			true
		);
	}

	/**
	 * Output a single plugin-row inside the core WP update-plugins list.
	 *
	 * Though the name says "plugin_row", this function is also used to render
	 * rows inside the themes-update list. Code is identical.
	 *
	 * @param string $file        The plugin ID (dir- and filename).
	 * @param array  $plugin_data Plugin details.
	 *
	 * @since  4.0.5
	 *
	 */
	public function brand_updates_plugin_row( $file, $plugin_data ) {
		// Get new version and update URL.
		$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );

		if ( ! is_array( $updates ) || ! count( $updates ) ) {
			return;
		}
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$project = false;

		foreach ( $updates as $id => $plugin ) {
			$slug = 'theme' === $plugin['type'] ? dirname( $plugin['filename'] ) : $plugin['filename'];
			if ( $slug === $file ) {
				$project_id = $id;
				$project    = $plugin;
				break;
			}
		}

		if ( $project ) {
			$this->brand_updates_row_output( $project_id, $project, $plugin_data['Name'] );
		}
	}

	/**
	 * Shared helper used by brand_updates_* functions above.
	 * This function actually renders the table row with the update text.
	 *
	 * @param int    $project_id   Our internal project-ID.
	 * @param array  $project      The project details.
	 * @param string $project_name The plugin/theme name.
	 *
	 * @since  4.0.5
	 *
	 */
	protected function brand_updates_row_output( $project_id, $project, $project_name ) {
		$version    = $project['new_version'];
		$plugin_url = $project['url'];
		$autoupdate = $project['autoupdate'];
		$filename   = $project['filename'];

		$plugins_allowedtags = array(
			'a'       => array(
				'href'     => array(),
				'title'    => array(),
				'class'    => array(),
				'target'   => array(),
				'data-pid' => array(),
			),
			'abbr'    => array( 'title' => array() ),
			'acronym' => array( 'title' => array() ),
			'code'    => array(),
			'em'      => array(),
			'strong'  => array(),
		);

		$plugin_name = wp_kses( $project_name, $plugins_allowedtags );

		$url_action = false;

		if ( WPMUDEV_Dashboard::$upgrader->user_can_install( $project_id ) ) {
			// Current user is logged in and has permission for this plugin.
			if ( $autoupdate ) {
				// All clear: One-Click-Update is available for this plugin!
				$url_action = WPMUDEV_Dashboard::$upgrader->auto_update_url( $project_id );
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="#" class="wpmudev-dashboard-changelog-btn" data-pid="%2$s" title="%3$s">View version %4$s details</a> or <a href="%5$s" class="update-link">update now</a>.', 'wpmudev' );
			} else {
				// Can only be manually installed.
				$url_action = $plugin_url;
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="#" class="wpmudev-dashboard-changelog-btn" data-pid="%2$s" title="%3$s">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Download update from WPMU DEV">download update</a>.', 'wpmudev' );
			}
		} elseif ( WPMUDEV_Dashboard::$site->allowed_user() ) {
			// User has no permission for the plugin (anymore).
			if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
				// Ah, the user is not logged in... update currently not available.
				$url_action = $this->page_urls->dashboard_url;
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="#" class="wpmudev-dashboard-changelog-btn" data-pid="%2$s" title="%3$s">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Setup your WPMU DEV account to update">login to update</a>.', 'wpmudev' );
			} else {
				// User is logged in but apparently no license for the plugin.
				$url_action = apply_filters(
					'wpmudev_project_upgrade_url',
					$this->page_urls->remote_site . 'wp-login.php?redirect_to=' . rawurlencode( $plugin_url ) . '#signup',
					$project_id
				);
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="#" class="wpmudev-dashboard-changelog-btn" data-pid="%2$s" title="%3$s">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Upgrade your WPMU DEV membership">upgrade to update</a>.', 'wpmudev' );
			}
		} else {
			// This user has no permission to use WPMUDEV Dashboard.
			$row_text = __( 'There is a new version of %1$s available on WPMU DEV. <a href="#" class="wpmudev-dashboard-changelog-btn" data-pid="%2$s"  title="%3$s">View version %4$s details</a>.', 'wpmudev' );
		}

		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( $filename ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( $filename ) ? ' active' : '';
		}

		?>
		<tr class="plugin-update-tr<?php echo esc_attr( $active_class ); ?>"
		    id="<?php echo esc_attr( dirname( $filename ) ); ?>-update"
		    data-slug="<?php echo esc_attr( dirname( $filename ) ); ?>"
		    data-plugin="<?php echo esc_attr( $filename ); ?>">
			<td colspan="3" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-warning notice-alt">
					<p>
						<?php
						printf(
							wp_kses( $row_text, $plugins_allowedtags ),
							esc_html( $plugin_name ),
							intval( $project_id ),
							esc_attr( $plugin_name ),
							esc_html( $version ),
							esc_url( $url_action )
						);
						?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output a single theme-row inside the core WP update-themes list.
	 *
	 * This handler is only used when updates are available for the farm133
	 * themepack, all other themes are handled by the `brand_updates_plugin_row`
	 * handler above.
	 *
	 * @param string $file        The theme slug.
	 * @param array  $plugin_data Theme details.
	 *
	 * @since  4.0.5
	 *
	 */
	public function brand_updates_farm133_row( $file, $plugin_data ) {
		// Get new version and update URL.
		$updates      = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
		$id_themepack = WPMUDEV_Dashboard::$site->id_farm133_themes;

		if ( ! isset( $updates[ $id_themepack ] ) ) {
			return;
		}
		if ( ! current_user_can( 'update_themes' ) ) {
			return;
		}

		$project = $updates[ $id_themepack ];
		$this->brand_updates_row_output( $id_themepack, $project, $plugin_data['Name'] );
	}

	/**
	 * Called on update-core.php after the list of available updates is printed.
	 * We use this opportunty to inset javascript to modify the update-list
	 * since there are no exising hooks in WP to do this on PHP side:
	 *
	 * Some plugins/themes might not support auto-update. Those items must be
	 * disabled here!
	 *
	 * @since  4.1.0
	 */
	public function modify_core_updates_page() {
		$is_logged_in = WPMUDEV_Dashboard::$api->has_key();
		$allowed_user = WPMUDEV_Dashboard::$site->allowed_user();
		$projects     = WPMUDEV_Dashboard::$site->get_cached_projects();

		$plugins = array();
		foreach ( $projects as $pid => $data ) {
			// Get project info.
			$item = WPMUDEV_Dashboard::$site->get_project_info( $pid );
			if ( ! $item ) {
				continue;
			}

			if ( 'plugin' === $item->type ) {
				$action_html = '';
				// If Dash is not connected.
				if ( ! $is_logged_in ) {
					// translators: %s link to dashboard.
					$action_html = sprintf( __( '<a href="%s">Login to WPMU DEV Dashboard</a> to update', 'wpmudev' ), esc_url( $this->page_urls->dashboard_url ) );
				} elseif ( $allowed_user ) {
					// If auto update is disabled.
					$action_html = __( 'Auto-update not possible.', 'wpmudev' );
					if ( ! empty( $item->url->infos ) ) {
						// translators: %s link to dashboard.
						$action_html = $action_html . ' ' . sprintf( __( '<a href="%s">More info &raquo;</a>', 'wpmudev' ), esc_url( $item->url->infos ) );
					}
				}

				// Set plugin data.
				$plugins[] = array(
					'pid'         => $item->pid,
					'file'        => $item->filename,
					'name'        => $item->name,
					'disabled'    => ! $item->can_update || ! $item->can_autoupdate,
					'action_html' => empty( $action_html ) ? '' : '<div class="wpmudev-info">' . $action_html . '</div>',
				);
			}
		}

		if ( ! empty( $plugins ) ) {
			// Enqueue assets.
			wp_enqueue_style( 'wpmudev-dashboard-changelog' );
			wp_enqueue_script( 'wpmudev-dashboard-changelog' );

			// Localized vars.
			wp_localize_script(
				'wpmudev-dashboard-changelog',
				'wpmudevDashboard',
				array(
					'nonce'   => wp_create_nonce( 'show-popup' ),
					'plugins' => $plugins,
				)
			);

			// Render template.
			$this->render( 'sui/popup-changelog' );
		}
	}

	/**
	 * Setup the analytics dashboard widgets.
	 *
	 * @internal Action hook
	 * @since    4.6
	 * @uses     $wp_locale
	 */
	public function analytics_widget_setup() {
		$analytics_enabled = WPMUDEV_Dashboard::$site->get_option( 'analytics_enabled' );
		if ( is_wpmudev_member() && $analytics_enabled ) {
			global $wp_locale;
			if ( is_blog_admin() && WPMUDEV_Dashboard::$site->user_can_analytics() ) {
				wp_add_dashboard_widget( 'wdpun_analytics', __( 'Analytics', 'wpmudev' ), array( $this, 'render_analytics_widget' ) );
			}

			if ( is_network_admin() ) {
				wp_add_dashboard_widget( 'wdpun_analytics_network', __( 'Network Analytics', 'wpmudev' ), array( $this, 'render_analytics_widget' ) );
			}

			// Enqueue styles =====================================================.
			/*
			 * Beta-testers will not have cached scripts!
			 * Just in case we have to update the plugin prior to launch.
			 */
			if ( defined( 'WPMUDEV_BETATEST' ) && WPMUDEV_BETATEST ) {
				$script_version = time();
			} else {
				$script_version = WPMUDEV_Dashboard::$version;
			}
			wp_enqueue_style(
				'wpmudev-widget-analytics-css',
				WPMUDEV_Dashboard::$site->plugin_url . 'assets/css/dashboard-widget.min.css',
				array(),
				$script_version
			);
			// Register scripts ===================================================.
			wp_enqueue_script( 'wpmudev-moment-js', WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/moment.min.js', array(), '2.22.2', true );
			// adding handler to remove conflict with bundled version.
			wp_enqueue_script( 'chart-js-unbundled', WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/chart.min.js', array( 'wpmudev-moment-js' ), '2.7.2', true );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script(
				'wpmudev-dashboard-widget',
				WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-widget.js',
				array( 'jquery', 'chart-js-unbundled', 'jquery-ui-widget', 'jquery-ui-autocomplete' ),
				$script_version,
				true
			);

			// make chart data available to js
			$days_ago = ( isset( $_REQUEST['analytics_range'] ) && in_array( (int) $_REQUEST['analytics_range'], array( 1, 7, 30, 90 ), true ) )// wpcs: csrf ok.
				? absint( $_REQUEST['analytics_range'] ) : 7;
			if ( is_network_admin() || ! is_multisite() ) {
				$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago );
			} else {
				$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago, get_current_blog_id() );
			}

			$user_locale = get_locale();

			// get_user_locale only available since WP 4.7.0
			if ( function_exists( 'get_user_locale' ) ) {
				$user_locale = get_user_locale();
			}

			wp_localize_script(
				'wpmudev-dashboard-widget',
				'wdp_analytics_ajax',
				array(
					'nonce'           => wp_create_nonce( 'analytics' ),
					'overall_data'    => isset( $data['overall'] ) ? $data['overall'] : array(),
					'current_data'    => isset( $data['overall'] ) ? $data['overall'] : array(),
					'autocomplete'    => isset( $data['autocomplete'] ) ? $data['autocomplete'] : array(),
					'locale_settings' => array(
						'locale'      => $user_locale,
						'monthsShort' => array_values( $wp_locale->month_abbrev ),
						'weekdays'    => array_values( $wp_locale->weekday ),
					),
				)
			);
		}
	}

	/**
	 * Get's a list of tags for given project type. Used for search or dropdowns.
	 *
	 * @param string $type [plugin|theme].
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function tags_data( $type ) {
		$res  = array();
		$data = WPMUDEV_Dashboard::$api->get_projects_data();

		if ( 'plugin' === $type ) {
			if ( isset( $data['plugin_tags'] ) ) {
				$tags       = (array) $data['plugin_tags'];
				$known_tags = array(
					32  => __( 'Business', 'wpmudev' ),
					50  => __( 'SEO', 'wpmudev' ),
					498 => __( 'Marketing', 'wpmudev' ),
					31  => __( 'Publishing', 'wpmudev' ),
					29  => __( 'Community', 'wpmudev' ),
					489 => __( 'BuddyPress', 'wpmudev' ),
					16  => __( 'Multisite', 'wpmudev' ),
				);

				// Important: Index 0 is "All", added automatically.
				$tag_index = 1;
				foreach ( $known_tags as $tag_id => $tag_name ) {
					if ( ! isset( $tags[ $tag_id ] ) ) {
						continue;
					}
					$res[ $tag_index ] = array(
						'name' => $tag_name,
						'pids' => (array) $tags[ $tag_id ]['pids'],
					);
					$tag_index ++;
				}
			}
		} elseif ( 'theme' === $type ) {
			if ( isset( $data['theme_tags'] ) ) {
				$res = (array) $data['theme_tags'];
			}
		}

		return $res;
	}

	public function wp_popup_changelog( $pid ) {
		$this->render_with_sui_wrapper(
			'sui/popup-wordpress-changelog',
			compact( 'pid' )
		);

		exit;
	}


	public function render_project( $pid, $other_pids = false, $message = false, $withmenu = false ) {
		$as_json = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( $as_json ) {
			ob_start();
		}

		$membership_type = WPMUDEV_Dashboard::$api->get_membership_type();
		$membership_data = WPMUDEV_Dashboard::$api->get_membership_data();
		$hide_row        = false;

		$urls = $this->page_urls;
		$this->render(
			'sui/element-project-info',
			compact( 'pid', 'urls', 'membership_type', 'membership_data', 'hide_row' )
		);

		if ( $as_json ) {
			$code = ob_get_clean();
			$data = array( 'html' => $code );

			// Optionally include other projets in AJAX response.
			if ( $other_pids && is_array( $other_pids ) ) {
				$data['other'] = array();
				foreach ( $other_pids as $pid2 ) {
					ob_start();
					$this->render(
						'sui/element-project-info',
						array(
							'pid' => $pid2,
							'urls',
							'membership_type',
							'membership_data',
						)
					);
					$code                   = ob_get_clean();
					$data['other'][ $pid2 ] = $code;
				}
			}

			if ( $message ) {
				ob_start();
				$this->render( $message, compact( 'pid' ) );
				$code            = ob_get_clean();
				$data['overlay'] = $code;
			}

			if ( $withmenu ) {
				// Get the current wp-admin menu HTML code.
				$data['admin_menu'] = $this->get_admin_menu();
			}

			wp_send_json_success( $data );
		}

	}

	/**
	 * Add plugin modal & after install modal.
	 *
	 * @param string $pid Project ID.
	 *
	 * @since  4.9.4
	 */
	public function render_alt_project( $pid ) {

		$membership_type = WPMUDEV_Dashboard::$api->get_membership_type();
		$membership_data = WPMUDEV_Dashboard::$api->get_membership_data();
		$hide_row        = true;

		$urls = $this->page_urls;
		$this->render(
			'sui/element-project-info',
			compact( 'pid', 'urls', 'membership_type', 'membership_data', 'hide_row' )
		);

	}

	/**
	 * Fetches the admin-menu of the current user via remote get.
	 *
	 * @since  1.0.0
	 * @return string The menu HTML.
	 */
	protected function get_admin_menu() {
		if ( isset( $_GET['fetch_menu'] ) && 1 == $_GET['fetch_menu'] ) {
			// Avoid recursion...
			return '';
		}

		$url     = false;
		$cookies = array();
		$menu    = '';

		$url = add_query_arg(
			array( 'fetch_menu' => 1 ),
			wp_get_referer()
		);

		foreach ( $_COOKIE as $name => $value ) {
			$cookies[] = new WP_Http_Cookie(
				array(
					'name'  => $name,
					'value' => $value,
				)
			);
		}

		$request = wp_remote_get(
			$url,
			array(
				'timeout' => 4,
				'cookies' => $cookies,
			)
		);
		$body    = wp_remote_retrieve_body( $request );
		$menu    = substr( $body, strpos( $body, '<div id="wpwrap">' ) + 17 );
		$menu    = '<div>' . trim( $menu ) . '</div>';

		return $menu;
	}

	public function show_popup( $type, $pid = 0 ) {
		$as_json = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( $as_json ) {
			ob_start();
		}

		switch ( $type ) {
			// Project-Info/overview.
			case 'info':
				$this->render(
					'sui/popup-project',
					compact( 'pid' )
				);
				break;

			// Update information. // deprecated
			case 'update':
				$this->render(
					'popup-update-info',
					compact( 'pid' )
				);
				break;

			// Show the changelog.  // deprecated
			case 'changelog':
				$this->render(
					'popup-project-changelog',
					compact( 'pid' )
				);
				break;
			// Show the changelog for updates page.
			case 'updates_changelog':
				$this->render(
					'sui/popup-changelog-content',
					compact( 'pid' )
				);
				break;
			default:
				break;
		}

		if ( $as_json ) {
			$code = ob_get_clean();
			wp_send_json_success( array( 'html' => $code ) );
		}

	}

	/**
	 * Redirect to the specified URL, even after page output already started.
	 *
	 * @param string $url The URL.
	 *
	 * @since  4.0.0
	 *
	 */
	public function redirect_to( $url ) {
		if ( headers_sent() ) {
			printf(
				'<script>window.location.href="%s";</script>',
				$url
			); // wpcs xss ok.
		} else {
			header( 'X-Redirect-From: UI redirect_to' );
			wp_safe_redirect( $url );
		}
		exit;
	}

	/**
	 * Add link to WPMU DEV Dashboard to the WP toolbar; only for multisite
	 * networks, since single-site admins always see the WPMU DEV menu item.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The toolbar handler object.
	 *
	 * @since  4.1.0
	 *
	 */
	public function setup_toolbar( $wp_admin_bar ) {
		if ( is_multisite() ) {
			$args = array(
				'id'     => 'network-admin-d2',
				'title'  => 'WPMU DEV Dashboard',
				'href'   => $this->page_urls->dashboard_url,
				'parent' => 'network-admin',
			);

			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Add WPMUDEV link as return action after installing DEV plugins.
	 *
	 * Default actions are "Return to Themes/Plugins" and "Return to WP Updates"
	 * This filter adds a "Return to WPMUDEV Updates"
	 *
	 * @param array  $install_actions Array of further actions to display.
	 * @param object $api             The update API details.
	 * @param string $plugin_file     Main plugin file.
	 *
	 * @internal Action hook
	 *
	 * @since    1.0.0
	 * @return array
	 */
	public function branding_install_plugin_done( $install_actions, $api, $plugin_file ) {
		if ( ! empty( $api->download_link ) ) {
			if ( WPMUDEV_Dashboard::$api->is_server_url( $api->download_link ) ) {
				$install_actions['plugins_page'] = sprintf(
					'<a href="%s" title="%s" target="_parent">%s</a>',
					$this->page_urls->plugins_url,
					esc_attr__( 'Return to WPMU DEV Plugins', 'wpmudev' ),
					__( 'Return to WPMU DEV Plugins', 'wpmudev' )
				);
			}
		}

		return $install_actions;
	}

	/**
	 * Add WPMUDEV link as return action after upgrading DEV plugins.
	 *
	 * Default actions are "Return to Themes/Plugins" and "Return to WP Updates"
	 * This filter adds a "Return to WPMUDEV Updates"
	 *
	 * @param array  $update_actions Array of further actions to display.
	 * @param string $plugin         Main plugin file.
	 *
	 * @internal Action hook
	 *
	 * @since    1.0.0
	 * @return array
	 */
	public function branding_update_plugin_done( $update_actions, $plugin ) {
		$updates = WPMUDEV_Dashboard::$site->get_transient( 'update_plugins', false );

		if ( ! empty( $updates->response[ $plugin ] ) ) {
			if ( WPMUDEV_Dashboard::$api->is_server_url( $updates->response[ $plugin ]->package ) ) {
				$update_actions['plugins_page'] = sprintf(
					'<a href="%s" title="%s" target="_parent">%s</a>',
					$this->page_urls->plugins_url,
					esc_attr__( 'Return to WPMU DEV Plugins', 'wpmudev' ),
					__( 'Return to WPMU DEV Plugins', 'wpmudev' )
				);
			}
		}

		return $update_actions;
	}

	/**
	 * If a certain URL param is defined we will abort the request now.
	 *
	 * Handles the admin hook `in_admin_header`
	 * This hook is called after init and admin_init.
	 *
	 * @since  1.0.0
	 */
	public function maybe_return_menu() {
		$_get_data = $_GET;// wpcs csrf ok.
		if ( ! isset( $_get_data['fetch_menu'] ) ) {
			return;
		}
		if ( 1 !== (int) $_get_data['fetch_menu'] ) {
			return;
		}

		while ( ob_get_level() ) {
			ob_end_flush();
		}
		flush();
		wp_die();
	}

	/**
	 * Enqueue Dashboard styles on all non-dashboard admin pages.
	 *
	 * @internal Action hook
	 * @since    1.0.0
	 */
	public function notification_styles() {
		echo '<style>#toplevel_page_wpmudev .wdev-access-granted { font-size: 14px; line-height: 13px; height: 13px; float: right; color: #1ABC9C; }</style>';
	}

	/**
	 * Render upgrade highlights modal template.
	 *
	 * This modal will be automatically opened if it's
	 * rendered.
	 *
	 * @since 4.11
	 */
	public function render_highlights_modal() {
		// Only if not dismissed already.
		if (
			! WPMUDEV_Dashboard::$whitelabel->get_branding_hide_doc_link() &&
			! WPMUDEV_Dashboard::$site->get_option( 'highlights_dismissed' )
		) {
			$this->render( 'sui/popup-upgrade-highlights' );
		}
	}

	/**
	 * Register the WPMUDEV Dashboard menu structure.
	 *
	 * @internal Action hook
	 * @since    1.0.0
	 */
	public function setup_menu() {
		$is_logged_in   = WPMUDEV_Dashboard::$api->has_key();
		$count_output   = '';
		$remote_granted = false;
		$update_plugins = 0;
		$_get_data      = $_GET;// wpcs: csrf ok.

		// Redirect user, if we have a valid PID in URL param.
		if ( ! empty( $_get_data['page'] ) && 0 === strpos( $_get_data['page'], 'wpmudev' ) ) {
			if ( ! empty( $_get_data['pid'] ) && is_numeric( $_get_data['pid'] ) ) {
				$project = WPMUDEV_Dashboard::$site->get_project_info( $_get_data['pid'] );
				if ( $project ) {
					if ( 'plugin' === $project->type ) {
						$redirect = $this->page_urls->plugins_url . '#pid=' . $project->pid;
						WPMUDEV_Dashboard::$ui->redirect_to( $redirect );
					}
				}
			}
		}

		if ( $is_logged_in ) {
			// Show total number of available updates.
			$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
			if ( is_array( $updates ) ) {
				foreach ( $updates as $item ) {
					if ( 'plugin' === $item['type'] ) {
						$update_plugins ++;
					}
				}
			}
			$count = $update_plugins;

			if ( $count > 0 ) {
				$count_output = sprintf(
					'<span class="countval">%s</span>',
					$count
				);
			}
			$count_label   = array();
			$count_label[] = sprintf( _n( '%s Plugin update', '%s Plugin updates', $update_plugins, 'wpmudev' ), $update_plugins );

			$count_output = sprintf(
				' <span class="update-plugins total-updates count-%s" title="%s">%s</span>',
				$count,
				implode( ', ', $count_label ),
				$count_output
			);

			$staff_login    = WPMUDEV_Dashboard::$api->remote_access_details();
			$remote_granted = $staff_login->enabled;
		} else {
			// Show icon if user is not logged in.
			$count_output = sprintf(
				' <span style="float:right;margin:-1px 13px 0 0;vertical-align:top;border-radius:10px;background:#F8F8F8;width:18px;height:18px;text-align:center" title="%s">%s</span>',
				__( 'Log in to your WPMU DEV account to use all features!', 'wpmudev' ),
				'<i class="dashicons dashicons-lock" style="font-size:14px;width:auto;line-height:18px;color:#333"></i>'
			);
		}

		$need_cap = 'manage_options'; // Single site.
		if ( is_multisite() ) {
			$need_cap = 'manage_network_options'; // Multi site.
		}

		// Dashboard Main Menu.
		$page = add_menu_page(
			__( 'WPMU DEV Dashboard', 'wpmudev' ),
			__( 'WPMU DEV', 'wpmudev' ) . $count_output,
			$need_cap,
			'wpmudev',
			array( $this, 'render_dashboard' ),
			$this->get_menu_icon(),
			WPMUDEV_MENU_LOCATION
		);

		add_action( 'load-' . $page, array( $this, 'load_admin_sui_scripts' ) );

		$this->add_submenu(
			'wpmudev',
			__( 'WPMU DEV Dashboard', 'wpmudev' ),
			__( 'Dashboard', 'wpmudev' ),
			array( $this, 'render_dashboard' )
		);

		if ( $is_logged_in ) {
			/**
			 * Use this action to register custom sub-menu items.
			 *
			 * The action is called before each of the default submenu items
			 * is registered, so other plugins can hook into any position they
			 * like by checking the action parameter.
			 *
			 * @var  WPMUDEV_Dashboard_ui $ui   Use $ui->add_submenu() to register
			 *       new menu items.
			 * @var  string               $menu The menu-item that is about to be set up.
			 */
			do_action( 'wpmudev_dashboard_setup_menu', $this, 'plugins' );

			$plugin_badge = sprintf(
				' <span class="update-plugins plugin-updates wdev-update-count count-%1$s" data-count="%1$s"><span class="countval">%1$s</span></span>',
				$update_plugins
			);
			// Plugins page.
			$this->add_submenu(
				'plugins',
				__( 'WPMU DEV Plugins', 'wpmudev' ),
				__( 'Plugins', 'wpmudev' ) . $plugin_badge,
				array( $this, 'render_plugins' ),
				'install_plugins'
			);

			do_action( 'wpmudev_dashboard_setup_menu', 'support' );

			// Support page.
			$support_icon = '';
			if ( $remote_granted ) {
				$support_icon = sprintf(
					' <i class="dashicons dashicons-unlock wdev-access-granted" title="%s"></i>',
					__( 'Support Access enabled', 'wpmudev' )
				);
			}
			$this->add_submenu(
				'support',
				__( 'WPMU DEV Support', 'wpmudev' ),
				__( 'Support', 'wpmudev' ) . $support_icon,
				array( $this, 'render_support' ),
				$need_cap
			);

			do_action( 'wpmudev_dashboard_setup_menu', 'analytics' );
			$this->add_submenu(
				'analytics',
				__( 'WPMU DEV Analytics', 'wpmudev' ),
				__( 'Analytics', 'wpmudev' ),
				array( $this, 'render_analytics' ),
				$need_cap
			);

			do_action( 'wpmudev_dashboard_setup_menu', 'whitelabel' );
			$this->add_submenu(
				'whitelabel',
				__( 'WPMU DEV Whitelabel', 'wpmudev' ),
				__( 'Whitelabel', 'wpmudev' ),
				array( $this, 'render_whitelabel' ),
				$need_cap
			);

			// Manage (Settings).
			do_action( 'wpmudev_dashboard_setup_menu', 'settings' );
			$this->add_submenu(
				'settings',
				__( 'WPMU DEV Settings', 'wpmudev' ),
				__( 'Settings', 'wpmudev' ),
				array( $this, 'render_settings' ),
				$need_cap
			);

			do_action( 'wpmudev_dashboard_setup_menu', 'end' );
		}
	}

	/**
	 * Returns a base64 encoded SVG image that is used as Dashboard menu icon.
	 *
	 * Source image is file includes/images/logo.svg
	 * The source file is included with the plugin but not used.
	 *
	 * @since  4.0.0
	 * @return string Base64 encoded icon.
	 */
	protected function get_menu_icon() {
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
		?>
		<svg width="24px" height="24px" version="1.1" xmlns="http://www.w3.org/2000/svg">
			<g stroke="none" fill="#a0a5aa" fill-rule="evenodd">
				<path
					d="M12,0 C5.36964981,0 0,5.36964981 0,12 C0,18.6303502 5.36964981,24 12,24 C18.6303502,24 24,18.6303502 24,12 C24,5.36964981 18.6303502,0 12,0 L12,0 Z M19.5004228,4.1500001 L17.8398594,5.47845082 L17.8398594,14.3901411 C17.8398594,14.9436623 17.4523946,15.331127 17.0095777,15.331127 C16.5114087,15.331127 16.1239439,14.9436623 16.1239439,14.3901411 L16.1239439,9.62985934 C16.1239439,8.08000016 15.0169016,6.86225366 13.6330987,6.86225366 C12.2492959,6.86225366 11.1422536,8.08000016 11.1422536,9.62985934 L11.1422536,14.3901411 C11.1422536,14.9436623 10.7547888,15.331127 10.3119719,15.331127 C9.86915502,15.331127 9.48169023,14.9436623 9.48169023,14.3901411 L9.48169023,9.62985934 C9.48169023,8.08000016 8.37464795,6.86225366 6.99084511,6.86225366 C5.60704227,6.86225366 4.5,8.08000016 4.5,9.62985934 L4.5,9.62985934 L4.5,19.8700004 L6.10521129,18.5969017 L6.10521129,9.62985934 C6.10521129,9.13169032 6.49267609,8.68887341 6.99084511,8.68887341 C7.43366202,8.68887341 7.82112682,9.13169032 7.82112682,9.62985934 L7.82112682,14.3901411 C7.82112682,15.9400003 8.92816909,17.2130989 10.3119719,17.2130989 C11.6957748,17.2130989 12.802817,15.9400003 12.802817,14.3901411 L12.802817,14.3901411 L12.802817,9.62985934 C12.802817,9.13169032 13.1902818,8.68887341 13.6330987,8.68887341 C14.1312678,8.68887341 14.5187326,9.13169032 14.5187326,9.62985934 L14.5187326,14.3901411 C14.5187326,15.9400003 15.6257748,17.2130989 17.0095777,17.2130989 C18.3933805,17.2130989 19.5004228,15.9400003 19.5004228,14.3901411 L19.5004228,14.3901411 L19.5004228,4.1500001 L19.5004228,4.1500001 Z"></path>
			</g>
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Official way to add new submenu items to the WPMUDEV Dashboard.
	 * The Dashboard styles are automatically enqueued for the new page.
	 *
	 * @param string   $id         The ID is prefixed with 'wpmudev-' for the page body class.
	 * @param string   $title      The documents title-tag.
	 * @param string   $label      The menu label.
	 * @param callable $handler    Function that is executed to render page content.
	 * @param string   $capability Optional. Required capability. Default: manage_options.
	 *
	 * @since 4.0.0
	 *
	 * @return string Page hook_suffix of the new menu item.
	 */
	public function add_submenu( $id, $title, $label, $handler, $capability = 'manage_options' ) {
		static $registered = array();

		// Prevent duplicates of the same menu item.
		if ( isset( $registered[ $id ] ) ) {
			return '';
		}
		$registered[ $id ] = true;

		if ( false === strpos( $id, 'wpmudev' ) ) {
			$id = 'wpmudev-' . $id;
		}

		$page = add_submenu_page(
			'wpmudev',
			$title,
			$label,
			$capability,
			$id,
			$handler
		);

		add_action( 'load-' . $page, array( $this, 'load_admin_sui_scripts' ) );

		return $page;
	}

	/**
	 * Hide all default admin notices from another source on these pages.
	 */
	// public function remove_admin_notices() {
	// remove_all_actions( 'admin_notices' );
	// remove_all_actions( 'network_admin_notices' );
	// remove_all_actions( 'all_admin_notices' );

	// remove any custom contextual help tabs (like from Ultimate Branding)
	// $screen = get_current_screen();
	// $screen->remove_help_tabs();
	// }

	/**
	 * Sort the project list based on updates
	 */
	private function _sort_by_updates( $a, $b ) {

	}

	/**
	 * Sort the project list based on installation
	 */
	private function _sort_by_installed( $a, $b ) {

	}

	/**
	 * @return string
	 */
	public function get_current_screen_module() {
		$current_module = 'dashboard';

		// Find out what items to display in the search field.
		$screen = get_current_screen();

		if ( is_object( $screen ) ) {
			$base = (string) $screen->base;

			switch ( true ) {
				case false !== strpos( $base, 'plugins' ):
					$current_module = 'plugins';
					break;

				case false !== strpos( $base, 'support' ):
					$current_module = 'support';
					break;
				case false !== strpos( $base, 'analytics' ):
					$current_module = 'analytics';
					break;
				case false !== strpos( $base, 'whitelabel' ):
					$current_module = 'whitelabel';
					break;
				case false !== strpos( $base, 'settings' ):
					$current_module = 'settings';
					break;
				default:
					break;
			}
		}

		$is_logged_in = WPMUDEV_Dashboard::$api->has_key();
		if ( 'dashboard' === $current_module && ! $is_logged_in ) {
			$current_module = 'login';
		}

		return $current_module;
	}

	public function load_wdev_plugin_ui() {
		// Load/Enqueue the plugin UI module.
		WDEV_Plugin_Ui::load(
			WPMUDEV_Dashboard::$site->plugin_url . 'shared-ui/',
			'wpmud-' . $this->get_current_screen_module()
		);
	}

	/**
	 * Outputs the Main Dashboard admin page
	 *
	 * @internal Menu callback
	 * @since    1.0.0
	 */
	public function render_dashboard() {

		// These two variables are used in template login.php.
		$connection_error                = false;
		$key_valid                       = true;
		$site_limit_exceeded             = false;
		$non_hosting_site_limit_exceeded = false;
		$site_limit_num                  = 0;
		$available_hosting_sites         = 0;

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		// First login redirect is done.
		WPMUDEV_Dashboard::$site->set_option( 'redirected_v4', 1 );

		if ( ! empty( $_GET['clear_key'] ) ) {// wpcs csrf ok.
			// User requested to log-out.
			WPMUDEV_Dashboard::$site->logout();
		} elseif ( ! empty( $_REQUEST['set_apikey'] ) ) {// wpcs csrf ok.
			$url = add_query_arg(
				array(
					'view' => 'sync',
					'key'  => trim( $_REQUEST['set_apikey'] ), // wpcs csrf ok.
				),
				$this->page_urls->dashboard_url
			);
			$this->redirect_to( $url );
		} elseif ( ! empty( $_REQUEST['invalid_key'] ) ) {
			$key_valid = false;
		} elseif ( ! empty( $_REQUEST['connection_error'] ) ) {
			$connection_error = true;
		} elseif ( ! empty( $_REQUEST['site_limit_exceeded'] ) ) {
			$site_limit_exceeded = true;
			if ( ! empty( $_REQUEST['site_limit'] ) ) {
				$site_limit_num = absint( $_REQUEST['site_limit'] );
			}
			if ( ! empty( $_REQUEST['available_hosting_sites'] ) && $_REQUEST['available_hosting_sites'] > 0 ) {
				$available_hosting_sites = absint( $_REQUEST['available_hosting_sites'] );
			}
		}

		$is_logged_in = WPMUDEV_Dashboard::$api->has_key();
		$urls         = $this->page_urls;

		if ( ! $is_logged_in ) {
			// User did not log in to WPMUDEV -> Show login page!
			$this->render_with_sui_wrapper( 'sui/login', compact( 'key_valid', 'connection_error', 'site_limit_exceeded', 'non_hosting_site_limit_exceeded', 'urls', 'site_limit_num', 'available_hosting_sites' ) );
		} elseif ( ! WPMUDEV_Dashboard::$site->allowed_user() ) {
			// User has no permission to view the page.
			$this->render_with_sui_wrapper( 'sui/no_access' );
		} else {

			if ( ! isset( $_GET['fetch_menu'] ) || 1 !== (int) $_GET['fetch_menu'] ) { // wpcs: csrf ok.
				// scan changes for the new dashboard plugins and support widget/table.
				WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );
			}

			// dashboard
			$data            = WPMUDEV_Dashboard::$api->get_projects_data();
			$member          = WPMUDEV_Dashboard::$api->get_profile();
			$type            = WPMUDEV_Dashboard::$api->get_membership_type();
			$projects        = WPMUDEV_Dashboard::$api->get_membership_projects();
			$active_projects = $this->get_total_active_projects( $data['projects'], false );
			$projects_nr     = $this->get_total_projects( $data['projects'] );
			$update_plugins  = 0;
			$staff_login     = WPMUDEV_Dashboard::$api->remote_access_details();

			// tools settings
			$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
			$analytics_enabled   = WPMUDEV_Dashboard::$site->get_option( 'analytics_enabled' );
			$membership_data     = WPMUDEV_Dashboard::$site->get_option( 'membership_data' );
			$total_visits        = 0;

			// Get visits.
			if ( $analytics_enabled && WPMUDEV_Dashboard::$api->is_analytics_allowed() ) {
				$visits = WPMUDEV_Dashboard::$api->analytics_stats_overall();
				if ( ! empty( $visits['overall']['totals']['visits'] ) ) {
					$total_visits = $visits['overall']['totals']['visits'];
				}
			}

			// Show total number of available updates.
			$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
			if ( is_array( $updates ) ) {
				foreach ( $updates as $item ) {
					if ( 'plugin' === $item['type'] ) {
						$update_plugins ++;
					}
				}
			}

			$update_plugins = $update_plugins > 0 ? $update_plugins : __( 'All up to date', 'wpmudev' );

			$licensed_projects = array();
			// single membership.
			if ( $projects ) {
				if ( is_array( $projects ) ) {
					foreach ( $projects as $id ) {
						$licensed_projects[] = WPMUDEV_Dashboard::$site->get_project_info( $id );
					}
				} else {
					$licensed_projects[] = WPMUDEV_Dashboard::$site->get_project_info( $projects );
				}
			}

			/**
			 * Custom hook to display own notifications inside Dashboard.
			 */
			do_action( 'wpmudev_dashboard_notice-dashboard' );

			$this->render_with_sui_wrapper(
				'sui/dashboard',
				compact( 'data', 'member', 'urls', 'type', 'licensed_projects', 'projects_nr', 'active_projects', 'update_plugins', 'staff_login', 'whitelabel_settings', 'analytics_enabled', 'total_visits', 'membership_data' )
			);
		}
	}

	/**
	 * Loads the specified template.
	 *
	 * The template name should only contain the filename, without the .php
	 * extension, and without the template/ folder.
	 * If you want to pass variables to the template use the $data parameter
	 * and specify each variable as an array item. The array key will become the
	 * variable name.
	 *
	 * Using this function offers other plugins two filters to output content
	 * before or after the actual template.
	 *
	 * E.g.
	 *   render( 'no_access', array( 'msg' => 'test' ) );
	 *   will load the file template/no_access.php and pass it variable $msg
	 *
	 * Views:
	 *   If the REQUEST variable 'view' is set, then this function will attempt
	 *   to load the template file <name>-<view>.php with fallback to default
	 *   <name>.php if the view file does not exist.
	 *
	 * @param string $name The template name.
	 * @param array  $data Variables passed to the template, key => value pairs.
	 *
	 * @since  4.0.0
	 *
	 */
	public function render( $name, $data = array() ) {
		if ( ! empty( $_REQUEST['view'] ) ) {// wpcs csrf ok.
			$view   = strtolower( sanitize_html_class( $_REQUEST['view'] ) );
			$file_1 = $name . '-' . $view . '.php';
			$file_2 = $name . '.php';
		} else {
			$file_1 = $name . '.php';
			$file_2 = $name . '.php';
		}

		$path_1 = WPMUDEV_Dashboard::$site->plugin_path . 'template/' . $file_1;
		$path_2 = WPMUDEV_Dashboard::$site->plugin_path . 'template/' . $file_2;

		$path = false;
		if ( file_exists( $path_1 ) ) {
			$path = $path_1;
		} elseif ( file_exists( $path_2 ) ) {
			$path = $path_2;
		}

		if ( $path ) {
			/**
			 * Output some content before the template is loaded, or modify the
			 * variables passed to the template.
			 *
			 * @var  array $data The
			 */
			$new_data = apply_filters( 'wpmudev_dashboard_before-' . $name, $data );
			if ( isset( $new_data ) && is_array( $new_data ) ) {
				$data = $new_data;
			}

			extract( $data );
			require $path;

			/**
			 * Output code or do stuff after the template was loaded.
			 */
			do_action( 'wpmudev_dashboard_after-' . $name );
		} else {
			printf(
				'<div class="error"><p>%s</p></div>',
				sprintf(
					esc_html__( 'Error: The template %s does not exist. Please re-install the plugin.', 'wpmudev' ),
					'"' . esc_html( $name ) . '"'
				)
			);
		}
	}

	public function render_with_sui_wrapper( $name, $data = array() ) {
		echo '<main class="sui-wrap">';
		$this->render( $name, $data );
		echo '</main>';
	}

	/**
	 * Count projects by type from list
	 *
	 * @param array $projects List of projects from api call
	 *
	 * @return array
	 */
	protected function get_total_projects( $projects ) {
		$count = array(
			'plugins' => 0,
			'themes'  => 0,
			'all'     => 0,
		);
		foreach ( $projects as $project ) {
			$project_info = WPMUDEV_Dashboard::$site->get_project_info( $project['id'] );

			// skip hidden/deprecated plugins
			if ( $project_info->is_hidden ) {
				continue;
			}

			if ( 'plugin' === $project['type'] ) {
				$count['plugins'] ++;
			} elseif ( 'theme' === $project['type'] ) {
				// remove Upfront parent theme from list
				if ( 'Upfront' === $project['name'] ) {
					continue;
				}

				$count['themes'] ++;
			}
		}

		$count['all'] = $count['plugins'] + $count['themes'];

		return $count;
	}

	/**
	 * Count active projects by type from list
	 *
	 * @param array $projects List of projects from api call
	 *
	 * @return array
	 */
	protected function get_total_active_projects( $projects, $ignore_dash = true ) {
		$count = array(
			'plugins' => 0,
			'themes'  => 0,
			'all'     => 0,
		);
		foreach ( $projects as $project ) {
			$project_info = WPMUDEV_Dashboard::$site->get_project_info( $project['id'] );

			// skip hidden/deprecated plugins
			if ( $project_info->is_hidden ) {
				continue;
			}

			if ( ! $project_info->is_active ) {
				continue;
			}

			if ( $ignore_dash && 119 === $project['id'] ) {
				continue;
			}

			if ( 'plugin' === $project['type'] ) {
				$count['plugins'] ++;
			} elseif ( 'theme' === $project['type'] ) {
				// remove Upfront parent theme from list
				if ( 'Upfront' === $project['name'] ) {
					continue;
				}

				$count['themes'] ++;
			}
		}

		$count['all'] = $count['plugins'] + $count['themes'];

		return $count;
	}

	public function add_sui_body_class( $classes ) {
		$current_module = $this->get_current_screen_module();
		$classes        .= ' wpmudevdash wpmud-' . $current_module . ' ' . WPMUDEV_Dashboard::$sui_version;

		if ( 'login' === $current_module ) {

			if ( isset( $_GET['view'] ) && ( 'system' === $_GET['view'] ) ) {
				$classes .= '';
			} else {
				$classes .= ' wpmudev-onboarding ';
			}
		} else {

			if ( isset( $_GET['view'] ) && ( 'sync-plugins' === $_GET['view'] ) ) {
				$classes .= ' wpmudev-onboarding ';
			}
		}

		return $classes;
	}

	/**
	 * @internal Action hook
	 */
	public function load_admin_sui_scripts() {
		add_filter( 'admin_body_class', array( $this, 'add_sui_body_class' ) );
		$script_version = WPMUDEV_Dashboard::$version;

		// Enqueue styles =====================================================.
		wp_enqueue_style(
			'wpmudev-sui-admin-css',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/css/dashboard-admin.min.css',
			array(),
			$script_version
		);

		// Register scripts ===================================================.
		wp_enqueue_script(
			'wpmudev-dashboard-admin-js',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-admin.min.js',
			array( 'jquery' ),
			$script_version,
			true
		);

		wp_localize_script(
			'wpmudev-dashboard-admin-js',
			'wdp_locale',
			array(
				'updating_plugin'                     => __( 'Updating %s ...', 'wpmudev' ),
				'activating_plugin'                   => __( 'Activating %s ...', 'wpmudev' ),
				'installing_plugin'                   => __( 'Installing %s ...', 'wpmudev' ),
				'deactivating_plugin'                 => __( 'Deactivating %s ...', 'wpmudev' ),
				'deleting_plugin'                     => __( 'Deleting %s ...', 'wpmudev' ),
				'installing_activating_plugin'        => __( 'Installing and activating %s ...', 'wpmudev' ),
				'installing_translation'              => __( 'Installing %s translation...', 'wpmudev' ),
				'translation_updated'                 => __( '%s translations successfully updated.', 'wpmudev' ),
				'no_result_search_plugin_all'         => __( 'There are no plugins matching your search, please try again.', 'wpmudev' ),
				'no_result_search_plugin_activated'   => __( 'There are no active plugins matching your search, please try again.', 'wpmudev' ),
				'no_result_search_plugin_deactivated' => __( 'There are no deactivated plugins matching your search, please try again.', 'wpmudev' ),
				'no_result_search_plugin_updates'     => __( 'There are no plugins with updates available matching your search, please try again.', 'wpmudev' ),
				'no_plugin_activated'                 => __( "You don't have any WPMU DEV plugins installed and activated.", 'wpmudev' ),
				'no_plugin_deactivated'               => __( "You don't have any deactivated WPMU DEV plugins.", 'wpmudev' ),
				'no_plugin_updates'                   => __( 'There are no WPMU DEV plugin updates available.', 'wpmudev' ),
			)
		);

		// add_action( 'in_admin_header', array( $this, 'remove_admin_notices' ), 99 );
	}

	/**
	 * Renders the template header that is repeated on every page.
	 *
	 * @param string $page_title The page caption.
	 *
	 * @since  4.7
	 *
	 */
	public function render_sui_header( $page_title, $page_slug ) {
		$is_logged_in      = WPMUDEV_Dashboard::$api->has_key();
		$urls              = $this->page_urls;
		$url_support       = $urls->support_url;
		$url_dash          = $urls->hub_url;
		$url_logout        = $urls->dashboard_url . '&clear_key=1';
		$documentation_url = $urls->documentation_url[ $page_slug ];

		$member  = WPMUDEV_Dashboard::$api->get_profile();
		$profile = $member['profile'];

		$this->render(
			'sui/header',
			compact( 'page_title', 'is_logged_in', 'url_dash', 'url_logout', 'profile', 'url_support', 'documentation_url' )
		);

		$data = array();

		if ( ! isset( $_GET['wpmudev_msg'] ) ) { // wpcs csrf ok.
			$err = isset( $_GET['failed'] ) ? intval( $_GET['failed'] ) : false; // wpcs csrf ok.
			$ok  = isset( $_GET['success'] ) ? intval( $_GET['success'] ) : false; // wpcs csrf ok.

			if ( $ok && $ok >= time() ) {
				$data[] = 'WDP.showSuccess()';
			} elseif ( $err && $err >= time() ) {
				$data[] = 'WDP.showError()';
			}
		}

		// print_r( $data );
		// WDEV_Plugin_Ui::output( $data );

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice' );
	}

	/**
	 * Outputs the Analytics dashboard widget
	 *
	 * @internal Menu callback
	 * @since    4.6
	 */
	public function render_analytics_widget() {
		$this->render( 'widget-analytics' );
	}

	public function render_plugins() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		if ( ! isset( $_GET['fetch_menu'] ) || 1 !== (int) $_GET['fetch_menu'] ) { // wpcs: csrf ok.
			// When Plugins page is opened we always scan local folders for changes.
			WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );
		}

		$data            = WPMUDEV_Dashboard::$api->get_projects_data();
		$tags            = $this->tags_data( 'plugin' );
		$membership_type = WPMUDEV_Dashboard::$api->get_membership_type();
		$urls            = $this->page_urls;
		$active_projects = $this->get_total_active_projects( $data['projects'], false );
		if ( is_multisite() ) {
			$all_plugins = count( get_site_option( 'active_sitewide_plugins' ) );
		} else {
			$all_plugins = count( get_option( 'active_plugins' ) );
		}
		$member          = WPMUDEV_Dashboard::$api->get_profile();
		$update_plugins  = 0;
		$membership_data = WPMUDEV_Dashboard::$api->get_membership_data();
		$type            = WPMUDEV_Dashboard::$api->get_membership_type();
		$projects        = WPMUDEV_Dashboard::$api->get_membership_projects();

		// handles multiple snapshot project.
		$data = $this->handle_snapshot_v4( $data );

		// Show total number of available updates.
		$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
		if ( is_array( $updates ) ) {
			foreach ( $updates as $item ) {
				if ( 'plugin' === $item['type'] ) {
					$update_plugins ++;
				}
			}
		}

		// single membership.
		$licensed_projects = array();
		if ( is_array( $projects ) ) {
			foreach ( $projects as $p ) {
				$licensed_projects[] = WPMUDEV_Dashboard::$site->get_project_info( $p );
			}
		} else {
			$licensed_projects[] = WPMUDEV_Dashboard::$site->get_project_info( $projects );
		}

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-plugins' );

		$this->render_with_sui_wrapper(
			'sui/plugins',
			compact( 'data', 'urls', 'tags', 'update_plugins', 'membership_type', 'membership_data', 'active_projects', 'all_plugins', 'member', 'licensed_projects', 'type' )
		);
	}

	/**
	 * Handle snapshot v4
	 *
	 * @param Array $res result of get_project_infos().
	 *
	 * @since    4.9.1
	 */
	public function handle_snapshot_v4( $res ) {
		$snap_v3  = WPMUDEV_Dashboard::$site->get_project_info( 257 );
		$snap_v4  = WPMUDEV_Dashboard::$site->get_project_info( 3760011 );
		$projects = $res['projects'];

		// Show default.
		if ( ( $snap_v3 && $snap_v4 && $snap_v3->is_installed && $snap_v4->is_installed ) || ! $snap_v3 || ! $snap_v4 ) {
			return $res;
		}

		// Show v3.
		if ( $snap_v3 && $snap_v3->is_installed && ( ! $snap_v4 || ! $snap_v4->is_installed ) ) {
			$projects['3760011']['type'] = 'alt_plugin';
		}

		// Show v4.
		if ( ( $snap_v4 ) && ( ! $snap_v3 || ! $snap_v3->is_installed ) ) {
			$projects['257']['type'] = 'alt_plugin';
		}

		$res['projects'] = $projects;

		return $res;
	}

	/**
	 * Outputs the Support admin page.
	 *
	 * @internal Menu callback
	 * @since    1.0.0
	 */
	public function render_support() {
		$required = ( is_multisite() ? 'manage_network_options' : 'manage_options' );
		if ( ! current_user_can( $required ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		$this->page_urls->real_support_url = $this->page_urls->remote_site . 'hub/support/';

		$profile         = WPMUDEV_Dashboard::$api->get_profile();
		$data            = WPMUDEV_Dashboard::$api->get_projects_data();
		$urls            = $this->page_urls;
		$staff_login     = WPMUDEV_Dashboard::$api->remote_access_details();
		$notes           = WPMUDEV_Dashboard::$site->get_option( 'staff_notes' );
		$access          = WPMUDEV_Dashboard::$site->get_option( 'remote_access' );
		$membership_data = WPMUDEV_Dashboard::$site->get_option( 'membership_data' );
		$membership_type = WPMUDEV_Dashboard::$api->get_membership_type();

		if ( empty( $access['logins'] ) || ! is_array( $access['logins'] ) ) {
			$access_logs = array();
		} else {
			$access_logs = $access['logins'];
		}

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-support' );

		$this->render_with_sui_wrapper( 'sui/support', compact( 'profile', 'data', 'urls', 'staff_login', 'notes', 'access_logs', 'membership_data', 'membership_type' ) );
	}

	/**
	 * Render analytics page template.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_analytics() {
		// Get the capability.
		$required = ( is_multisite() ? 'manage_network_options' : 'manage_options' );
		// Render no access template.
		if ( ! current_user_can( $required ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		// Support media library usage.
		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		// Setup required variables for the template.
		$urls                = $this->page_urls;
		$membership_type     = WPMUDEV_Dashboard::$api->get_membership_type( $dummy );
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		$analytics_enabled   = WPMUDEV_Dashboard::$site->get_option( 'analytics_enabled' );
		$analytics_allowed   = WPMUDEV_Dashboard::$api->is_analytics_allowed();
		$analytics_role      = WPMUDEV_Dashboard::$site->get_option( 'analytics_role' );
		$analytics_role      = empty( $analytics_role ) ? 'administrator' : $analytics_role;
		$analytics_metrics   = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();
		$membership_data     = WPMUDEV_Dashboard::$site->get_option( 'membership_data' );

		// Custom hook to display own notifications inside Dashboard.
		do_action( 'wpmudev_dashboard_notice-tools' ); // phpcs:ignore

		// Render the template.
		$this->render_with_sui_wrapper(
			'sui/analytics',
			compact( 'urls', 'whitelabel_settings', 'analytics_enabled', 'analytics_allowed', 'analytics_role', 'analytics_metrics', 'membership_type', 'membership_data' )
		);
	}

	public function render_whitelabel() {
		$required        = ( is_multisite() ? 'manage_network_options' : 'manage_options' );
		$membership_data = WPMUDEV_Dashboard::$api->get_membership_data();
		$membership_type = $membership_data['membership'];

		if ( ! current_user_can( $required ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		// support media library usage
		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		$urls                = $this->page_urls;
		$data                = WPMUDEV_Dashboard::$api->get_projects_data();
		$projects            = empty( $data['projects'] ) ? array() : $data['projects'];
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		$analytics_enabled   = WPMUDEV_Dashboard::$site->get_option( 'analytics_enabled' );
		$analytics_role      = WPMUDEV_Dashboard::$site->get_option( 'analytics_role' );
		$analytics_role      = empty( $analytics_role ) ? 'administrator' : $analytics_role;
		$analytics_metrics   = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-tools' );

		$this->render_with_sui_wrapper( 'sui/whitelabel', compact( 'urls', 'projects', 'whitelabel_settings', 'analytics_enabled', 'analytics_role', 'analytics_metrics', 'membership_type', 'membership_data' ) );
	}

	public function render_settings() {
		$required = ( is_multisite() ? 'manage_network_options' : 'manage_options' );
		if ( ! current_user_can( $required ) ) {
			$this->render_with_sui_wrapper( 'sui/no_access' );
		}

		$member                  = WPMUDEV_Dashboard::$api->get_profile();
		$urls                    = $this->page_urls;
		$allowed_users           = WPMUDEV_Dashboard::$site->get_allowed_users();
		$available_users         = WPMUDEV_Dashboard::$site->get_available_users();
		$auto_update             = WPMUDEV_Dashboard::$site->get_option( 'autoupdate_dashboard' );
		$enable_sso              = WPMUDEV_Dashboard::$site->get_option( 'enable_sso' );
		$membership_type         = WPMUDEV_Dashboard::$api->get_membership_type();
		$enable_auto_translation = WPMUDEV_Dashboard::$site->get_option( 'enable_auto_translation' );
		$translation_update      = WPMUDEV_Dashboard::$site->get_option( 'translation_updates_available' );

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-settings' );

		$this->render_with_sui_wrapper( 'sui/settings', compact( 'member', 'urls', 'allowed_users', 'available_users', 'auto_update', 'enable_sso', 'membership_type', 'translation_update', 'enable_auto_translation' ) );
	}

	/**
	 * Display the header that tells the user to upgrade their membership.
	 *
	 * @param string $reason The reason why the user needs to upgrade.
	 *
	 * @since  4.9.0
	 *
	 */
	protected function render_upgrade_header( $reason, $licensed_projects ) {
		$is_logged_in = WPMUDEV_Dashboard::$api->has_key();
		$urls         = $this->page_urls;
		$user         = wp_get_current_user();
		$notice_id    = $this->_membership_notice;

		$username = $user->user_firstname;
		if ( empty( $username ) ) {
			$username = $user->display_name;
		}
		if ( empty( $username ) ) {
			$username = $user->user_login;
		}
		$membership_data = WPMUDEV_Dashboard::$site->get_option( 'membership_data' );
		$this->render(
			'sui/header-no-access',
			compact( 'is_logged_in', 'urls', 'username', 'reason', 'licensed_projects', 'notice_id', 'membership_data' )
		);
	}
}

// phpcs:ignore Generic.Files.OneClassPerFile.MultipleFound
class WPMUDEV_Dashboard_Sui_Page_Urls {
	public $dashboard_url = '';
	public $settings_url = '';
	public $plugins_url = '';
	public $support_url = '';
	public $tools_url = '';
	public $remote_site = 'https://wpmudev.com/';
	public $external_support_url = '';
	public $hub_url = 'https://wpmudev.com/hub2';
	public $hub_url_old = 'https://wpmudev.com/hub';
	public $documentation_url = array(
		'dashboard'  => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/',
		'plugins'    => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-plugin-manager',
		'support'    => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-support',
		'analytics'  => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-analytics',
		'whitelabel' => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-whitelabel',
		'settings'   => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#the-wpmu-dev-dashboard-plugin-settings',
	);
	public $community_url = 'https://wpmudev.com/hub2/community';
	public $academy_url = 'https://wpmudev.com/academy';
	public $hub_account_url = 'https://wpmudev.com/hub/account';
	public $trial_url = 'https://wpmudev.com/#trial';

	// backward compat
	public $real_support_url = '';
	public $themes_url = '';
	public $whip_url = '';
	public $blog_url = '';
	public $roadmap_url = '';

	public function __construct() {
		$url_callback = 'admin_url';
		if ( is_multisite() ) {
			$url_callback = 'network_admin_url';
		}
		$this->dashboard_url = call_user_func( $url_callback, 'admin.php?page=wpmudev' );
		$this->settings_url  = $this->dashboard_url;
		$this->plugins_url   = $this->dashboard_url;
		$this->support_url   = $this->dashboard_url;
		$this->tools_url     = $this->dashboard_url;

		if ( WPMUDEV_Dashboard::$api->has_key() ) {
			$this->settings_url   = call_user_func( $url_callback, 'admin.php?page=wpmudev-settings' );
			$this->plugins_url    = call_user_func( $url_callback, 'admin.php?page=wpmudev-plugins' );
			$this->support_url    = call_user_func( $url_callback, 'admin.php?page=wpmudev-support' );
			$this->tools_url      = call_user_func( $url_callback, 'admin.php?page=wpmudev-tools' );
			$this->analytics_url  = call_user_func( $url_callback, 'admin.php?page=wpmudev-analytics' );
			$this->whitelabel_url = call_user_func( $url_callback, 'admin.php?page=wpmudev-whitelabel' );
		}
		if ( WPMUDEV_CUSTOM_API_SERVER ) {
			$this->remote_site = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}

		$this->hub_url              = $this->remote_site . 'hub2';
		$this->documentation_url    = array(
			'dashboard'  => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/',
			'plugins'    => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-plugin-manager',
			'support'    => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-support',
			'analytics'  => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-analytics',
			'whitelabel' => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#wpmu-dev-dashboard-whitelabel',
			'settings'   => $this->remote_site . 'docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#the-wpmu-dev-dashboard-plugin-settings',
		);
		$this->external_support_url = $this->remote_site . 'hub/support/';
		$this->community_url        = $this->remote_site . 'hub/community/';
		$this->academy_url          = $this->remote_site . 'academy/';
		$this->hub_account_url      = $this->remote_site . 'hub/account';
		$this->blog_url             = $this->remote_site . 'blog';
		$this->whip_url             = $this->remote_site . 'blog/get-the-whip/';
		$this->roadmap_url          = $this->remote_site . 'roadmap/';
		$this->trial_url            = $this->remote_site . '#trial';
		$this->skip_trial_url       = $this->remote_site . 'hub/account/?skip_trial ';

	}
}