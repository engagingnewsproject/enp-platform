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

		add_filter( 'wp_prepare_themes_for_js', array( $this, 'hide_upfront_theme' ), 100 );

		add_action( 'load-plugins.php', array( $this, 'brand_updates_table' ), 21 ); // Must be called after WP which is 20.

		add_action( 'load-themes.php', array( $this, 'brand_updates_table' ), 21 ); // Must be called after WP which is 20.

		// Changelog modal.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_changelog_assets' ) );
		add_action( 'admin_footer', array( $this, 'changelog_modal' ) );

		// Some core updates need to be modified via javascript.
		add_action( 'core_upgrade_preamble', array( $this, 'modify_core_updates_page' ) );

		// Filter plugins page.
		add_action( 'all_plugins', array( $this, 'maybe_hide_dashboard' ) );

		// Analytics.
		add_action( 'wp_dashboard_setup', array( $this, 'analytics_widget_setup' ) );
		add_action( 'wp_network_dashboard_setup', array( $this, 'analytics_widget_setup' ) );
		// Analytics widget scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'analytics_widget_assets' ), 999 );

		// Render upgrade highlights modal.
		add_action( 'wpmudev_dashboard_ui_before_footer', array( $this, 'render_highlights_modal' ) );

		// Hide admin notices on login page.
		add_action( 'in_admin_header', array( $this, 'login_hide_admin_notices' ), 10000 );

		// Upsell notice.
		add_action( 'all_admin_notices', array( $this, 'expired_upsell_notice' ) );

		/**
		 * Run custom initialization code for the UI module.
		 *
		 * @var  WPMUDEV_Dashboard_Ui The dashboards UI module.
		 * @since  4.0.0
		 */
		do_action( 'wpmudev_dashboard_ui_init', $this );
	}

	/**
	 * Removes WPMU DEV Dashboard from native plugins page.
	 *
	 * @return void
	 */
	public function expired_upsell_notice() {
		// Do not continue.
		if ( ! $this->can_show_expired_upsell() ) {
			return;
		}

		// Enqueue assets.
		wp_enqueue_style(
			'wpmudev-dashboard-upsell',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/css/dashboard-upsell.min.css',
			array(),
			WPMUDEV_Dashboard::$version
		);

		wp_enqueue_script(
			'wpmudev-dashboard-upsell',
			WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-upsell.min.js',
			array( 'jquery' ),
			WPMUDEV_Dashboard::$version,
			true
		);

		wp_localize_script(
			'wpmudev-dashboard-upsell',
			'wpmudevDashboard',
			array(
				'extend_nonce'  => wp_create_nonce( 'extend-upsell' ),
				'dismiss_nonce' => wp_create_nonce( 'dismiss-upsell' ),
			)
		);

		// Render template.
		$this->render( 'sui/notice-upsell' );

		// Add modal template.
		add_action( 'admin_footer', array( $this, 'upsell_modal' ) );
	}

	/**
	 * Print upsell modal template.
	 *
	 * @since 4.11.15
	 *
	 * @return void
	 */
	public function upsell_modal() {
		// Render template.
		$this->render( 'sui/popup-upsell' );
	}

	/**
	 * Check if upsell can be shown.
	 *
	 * @since 4.11.15
	 *
	 * @return bool
	 */
	public function can_show_expired_upsell() {
		return (
			// Only on pages except Dashboard pages.
			! WPMUDEV_Dashboard::$utils->is_wpmudev_admin_page() &&
			// Only for expired membership.
			'expired' === WPMUDEV_Dashboard::$api->get_membership_status() &&
			// Only for WPMUDEV admin user.
			WPMUDEV_Dashboard::$site->allowed_user() &&
			// Only if not dismissed.
			! WPMUDEV_Dashboard::$settings->get( 'upsell_dismissed', 'flags' ) &&
			// Only if time has reached.
			WPMUDEV_Dashboard::$settings->get( 'upsell_notice_time', 'general', time() ) <= time()
		);
	}

	/**
	 * Removes WPMU DEV Dashboard from native plugins page.
	 *
	 * When?
	 * - White labeling is enabled
	 * - Current user is not a WPMUDEV admin.
	 *
	 * @param array $all_plugins List of installed plugins.
	 *
	 * @return array List of plugins.
	 */
	public function maybe_hide_dashboard( $all_plugins ) {
		// Only when a real user is logged in and it's wp-admin.
		if ( is_admin() && is_user_logged_in() ) {
			// Is current user a allowed user?.
			$allowed_user = WPMUDEV_Dashboard::$site->allowed_user();
			// Get whitelabel settings.
			$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();

			// Hide if not allowed user or white label is enabled.
			if ( ! $allowed_user || $whitelabel_settings['enabled'] ) {
				unset( $all_plugins[ WPMUDEV_Dashboard::$basename ] );
			}
		}

		return $all_plugins;
	}

	/**
	 * Hide admin notices on login page.
	 *
	 * @since 4.11.13
	 *
	 * @return void
	 */
	public function login_hide_admin_notices() {
		$screen = get_current_screen();

		// Hide only on our login page.
		if (
			isset( $screen->id ) &&
			in_array( $screen->id, array( 'toplevel_page_wpmudev', 'toplevel_page_wpmudev-network' ), true ) &&
			! WPMUDEV_Dashboard::$api->has_key()
		) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
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
			} elseif ( WPMUDEV_Dashboard::$settings->get( 'redirected_v4', 'flags' ) ) {
				// We already redirected the user to login page before.
				$redirect = false;
			}
		}

		/* ----- Save the flag and redirect if needed ----- */
		if ( $redirect ) {
			WPMUDEV_Dashboard::$settings->set( 'redirected_v4', true, 'flags' );

			// Force refresh of all data during first redirect.
			WPMUDEV_Dashboard::$settings->set( 'refresh_remote', true, 'flags' );
			WPMUDEV_Dashboard::$settings->set( 'refresh_profile', true, 'flags' );

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

		$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );
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
				$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );

				if ( ! empty( $updates ) ) {
					// Enqueue assets.
					wp_enqueue_script( 'wpmudev-dashboard-changelog' );
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
	 */
	public function brand_updates_plugin_row( $file, $plugin_data ) {
		// Get new version and update URL.
		$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );

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

		$url_action    = false;
		$url_changelog = self_admin_url( 'plugin-install.php' );
		$url_changelog = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => 'wpmudev_install-' . $project_id,
				'section'   => 'changelog',
				'TB_iframe' => 'true',
				'width'     => 600,
				'height'    => 800,
			),
			$url_changelog
		);

		$item = WPMUDEV_Dashboard::$site->get_project_info( $project_id );
		// Is compatible.
		$is_compatible = WPMUDEV_Dashboard::$upgrader->is_project_compatible( $project_id, $reason );

		if ( ! $is_compatible ) {
			if ( 'php' === $reason ) {
				// Incompatible PHP version.
				$row_text = __( 'There is a new version of %1$s available, but it is not compatible with your current PHP version. To update to the latest %1$s version, please upgrade your PHP to version %6$s or above. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a>.', 'wpmudev' );
			} else {
				// Other incompatibilities.
				$row_text = __( 'There is a new version of %1$s available, but it is not compatible. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a>.', 'wpmudev' );
			}
		} elseif ( WPMUDEV_Dashboard::$upgrader->user_can_install( $project_id ) ) {
			// Current user is logged in and has permission for this plugin.
			if ( $autoupdate ) {
				// All clear: One-Click-Update is available for this plugin!
				$url_action = WPMUDEV_Dashboard::$upgrader->auto_update_url( $project_id );
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a> or <a href="%5$s" class="update-link">update now</a>.', 'wpmudev' );
			} else {
				// Can only be manually installed.
				$url_action = $plugin_url;
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Download update from WPMU DEV">download update</a>.', 'wpmudev' );
			}
		} elseif ( WPMUDEV_Dashboard::$site->allowed_user() ) {
			// User has no permission for the plugin (anymore).
			if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
				// Ah, the user is not logged in... update currently not available.
				$url_action = $this->page_urls->dashboard_url;
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Setup your WPMU DEV account to update">login to update</a>.', 'wpmudev' );
			} else {
				// User is logged in but apparently no license for the plugin.
				$url_action = apply_filters(
					'wpmudev_project_upgrade_url',
					$this->page_urls->remote_site . 'wp-login.php?redirect_to=' . rawurlencode( $plugin_url ) . '#signup',
					$project_id
				);
				$row_text   = __( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a> or <a href="%5$s" target="_blank" title="Upgrade your WPMU DEV membership">upgrade to update</a>.', 'wpmudev' );
			}
		} else {
			// This user has no permission to use WPMUDEV Dashboard.
			$row_text = __( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" title="%3$s" class="thickbox open-plugin-details-modal">View version %4$s details</a>.', 'wpmudev' );
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
			<td colspan="4" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-warning notice-alt">
					<p>
						<?php
						printf(
							wp_kses( $row_text, $plugins_allowedtags ),
							esc_html( $plugin_name ),
							esc_url( $url_changelog ),
							esc_attr( $plugin_name ),
							esc_html( $version ),
							esc_url( $url_action ),
							$item->requires_min_php
						);
						?>
					</p>
					<?php
					/**
					 * Append content to an update notice (Only for Pro plugins).
					 *
					 * @since 4.11.13
					 *
					 * @param string $version    New version.
					 * @param array  $project    Project data (Will be empty if Dashboard plugin is not active).
					 *
					 * @param string $project_id Plugin ID.
					 */
					do_action( 'wpmudev_dashboard_after_update_row_message', $project_id, $version, $project );
					?>
				</div>
				<?php
				/**
				 * Add content after a plugin update notice (Only for Pro plugins).
				 *
				 * @since 4.11.13
				 *
				 * @param string $version    New version.
				 * @param array  $project    Project data (Will be empty if Dashboard plugin is not active).
				 *
				 * @param string $project_id Plugin ID.
				 */
				do_action( 'wpmudev_dashboard_after_update_row_content', $project_id, $version, $project );
				?>
				<?php if ( ! $is_compatible ) : ?>
					<script>
						let checkbox = jQuery('input:checkbox[value="<?php echo esc_attr( $filename ); ?>"]');
						checkbox.prop('disabled', true).prop('checked', false).attr('name', '').addClass('disabled');
					</script>
				<?php endif; ?>
			</td>
		</tr>
		<?php
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
				} elseif ( ! $allowed_user ) {
					// If auto update is disabled.
					$action_html = __( 'Auto-update not possible.', 'wpmudev' );
					if ( ! empty( $item->url->infos ) ) {
						// translators: %s link to dashboard.
						$action_html = $action_html . ' ' . sprintf( __( '<a href="%s">More info &raquo;</a>', 'wpmudev' ), esc_url( $item->url->infos ) );
					}
				} elseif ( ! $item->is_compatible && ! empty( $item->incompatible_reason ) ) {
					// If auto update is disabled.
					$action_html = sprintf( __( 'Update not possible: %s.', 'wpmudev' ), $item->incompatible_reason );
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
					'disabled'    => ! $item->can_update || ! $item->can_autoupdate || ! $item->is_compatible,
					'action_html' => empty( $action_html ) ? '' : '<div class="wpmudev-info" style="font-style: italic;">' . $action_html . '</div>',
				);
			}
		}

		if ( ! empty( $plugins ) ) {
			// Enqueue assets.
			wp_enqueue_script( 'wpmudev-dashboard-changelog' );

			// Localized vars.
			wp_localize_script(
				'wpmudev-dashboard-changelog',
				'wpmudevDashboard',
				array( 'plugins' => $plugins )
			);
		}
	}

	/**
	 * Setup the analytics dashboard widgets.
	 *
	 * Setup analytics charts and graphs for admin dashboard.
	 *
	 * @since    4.6
	 * @internal Action hook
	 * @uses     wp_add_dashboard_widget
	 *
	 * @return void
	 */
	public function analytics_widget_setup() {
		// Only if required.
		if ( $this->can_show_analytics_widget() ) {
			if ( is_blog_admin() && WPMUDEV_Dashboard::$site->user_can_analytics() ) {
				wp_add_dashboard_widget(
					'wdpun_analytics',
					__( 'Analytics', 'wpmudev' ),
					array( $this, 'render_analytics_widget' )
				);
			}

			// For network admin.
			if ( is_network_admin() ) {
				wp_add_dashboard_widget(
					'wdpun_analytics_network',
					__( 'Network Analytics', 'wpmudev' ),
					array( $this, 'render_analytics_widget' )
				);
			}
		}
	}

	/**
	 * Setup the analytics dashboard widgets assets.
	 *
	 * Enqueue style and scripts required on analytics widget.
	 *
	 * @internal Action hook
	 * @since    4.11.3
	 * @uses     $wp_locale
	 */
	public function analytics_widget_assets() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			// Continue only for WP admin dashboard page.
			if ( ! isset( $screen->id ) || ! in_array( $screen->id, array( 'dashboard', 'dashboard-network' ), true ) ) {
				return;
			}
		}

		// Only if required.
		if ( $this->can_show_analytics_widget() ) {
			global $wp_locale;

			// Beta-testers will not have cached scripts!
			// Just in case we have to update the plugin prior to launch.
			$script_version = defined( 'WPMUDEV_BETATEST' ) && WPMUDEV_BETATEST ? time() : WPMUDEV_Dashboard::$version;

			// Enqueue styles.
			wp_enqueue_style(
				'wpmudev-widget-analytics',
				WPMUDEV_Dashboard::$site->plugin_url . 'assets/css/dashboard-widget.min.css',
				array(),
				$script_version
			);

			// Our custom script.
			wp_enqueue_script(
				'wpmudev-dashboard-widget',
				WPMUDEV_Dashboard::$site->plugin_url . 'assets/js/dashboard-widget.min.js',
				array( 'jquery', 'jquery-ui-widget', 'jquery-ui-autocomplete' ),
				$script_version,
				true
			);

			// Get period for analytics.
			$days_ago = (
				isset( $_REQUEST['analytics_range'] ) && in_array( (int) $_REQUEST['analytics_range'], array( 1, 7, 30, 90 ), true ) // phpcs:ignore
			) ? absint( $_REQUEST['analytics_range'] ) : 7; // phpcs:ignore

			// For network admin and single sites.
			if ( is_network_admin() || ! is_multisite() ) {
				$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago );
			} else {
				// For sub sites.
				$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago, get_current_blog_id() );
			}

			// Locale.
			$user_locale = get_locale();

			// get_user_locale only available since WP 4.7.0.
			if ( function_exists( 'get_user_locale' ) ) {
				$user_locale = get_user_locale();
			}

			// Make all data available to our script.
			wp_localize_script(
				'wpmudev-dashboard-widget',
				'wdp_analytics_ajax',
				array(
					'nonce'           => wp_create_nonce( 'analytics' ),
					'overall_data'    => isset( $data['overall'] ) ? $data['overall'] : array(),
					'current_data'    => isset( $data['overall'] ) ? $data['overall'] : array(),
					'pages'           => isset( $data['pages'] ) ? $data['pages'] : array(),
					'authors'         => isset( $data['authors'] ) ? $data['authors'] : array(),
					'sites'           => isset( $data['sites'] ) ? $data['sites'] : array(),
					'autocomplete'    => isset( $data['autocomplete'] ) ? $data['autocomplete'] : array(),
					'network_flag'    => is_network_admin(),
					'subsite_flag'    => is_multisite() && ! is_network_admin(),
					'locale_settings' => array(
						'locale'      => $user_locale,
						'monthsShort' => array_values( $wp_locale->month_abbrev ),
						'weekdays'    => array_values( $wp_locale->weekday ),
					),
				)
			);

			$strings = array(
				'metrics' => array(),
				'tabs'    => array(
					'overview' => __( 'Overview', 'wpmudev' ),
					'pages'    => __( 'Top Pages & Posts', 'wpmudev' ),
					'authors'  => __( 'Authors', 'wpmudev' ),
					'sites'    => __( 'Top Sites', 'wpmudev' ),
				),
				'periods' => array(
					'yesterday' => __( 'Yesterday', 'wpmudev' ),
					'last7'     => __( 'Last 7 days', 'wpmudev' ),
					'last30'    => __( 'Last 30 days', 'wpmudev' ),
					'last90'    => __( 'Last 90 days', 'wpmudev' ),
				),
				'labels'  => array(
					'page_post'   => __( 'Page/Post title', 'wpmudev' ),
					'page'        => __( 'Page', 'wpmudev' ),
					'author'      => __( 'Author', 'wpmudev' ),
					'site'        => __( 'Site', 'wpmudev' ),
					'site_domain' => __( 'Site domain/name', 'wpmudev' ),
					'empty'       => __( 'We haven’t collected enough data of your website yet.', 'wpmudev' ),
					'try_again'   => __( 'Try again', 'wpmudev' ),
					'goto'        => __( 'Go to', 'wpmudev' ),
					'of'          => __( 'of', 'wpmudev' ),
					'show'        => __( 'Show', 'wpmudev' ),
					'data_for'    => __( 'data for', 'wpmudev' ),
				),
				'desc'    => array(
					'empty'      => __( 'You will start viewing the performance statistics of your website shortly. So feel free to check back soon', 'wpmudev' ),
					'temp_issue' => __( 'There was a temporary issue fetching analytics data. Please try again later.', 'wpmudev' ),
				),
			);

			$metrics = array(
				array(
					'key'  => 'pageviews',
					'name' => __( 'Page Views', 'wpmudev' ),
					'desc' => __( 'Total number of pages viewed. Repeated views of a single page are counted.', 'wpmudev' ),
				),
				array(
					'key'  => 'unique_pageviews',
					'name' => __( 'Unique Page Views', 'wpmudev' ),
					'desc' => __( 'The number of visits that included this page. If a page was viewed multiple times during one visit, it is only counted once.', 'wpmudev' ),
				),
				array(
					'key'  => 'page_time',
					'name' => __( 'Page Time', 'wpmudev' ),
					'desc' => __( 'The average amount of time visitors spent on a page.', 'wpmudev' ),
				),
				array(
					'key'  => 'visit_time',
					'name' => __( 'Visit Time', 'wpmudev' ),
					'desc' => __( 'The average amount of time visitors spent on the site.', 'wpmudev' ),
				),
				array(
					'key'  => 'visits',
					'name' => __( 'Entrances', 'wpmudev' ),
					'desc' => __( 'The number of time visitors entered your site through this page, from any source (e.g. search, direct, referral, etc.).', 'wpmudev' ),
				),
				array(
					'key'  => 'bounce_rate',
					'name' => __( 'Bounce Rate', 'wpmudev' ),
					'desc' => __( 'Single-page sessions. The percentage of visitors who left the website after their first page.', 'wpmudev' ),
				),
				array(
					'key'  => 'exit_rate',
					'name' => __( 'Exit Rate', 'wpmudev' ),
					'desc' => __( 'Number of exits divided by page views. Indicates percentage of exits from a specified page or average across your site.', 'wpmudev' ),
				),
			);

			// Enabled metrics.
			$selected_metrics = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();

			// Remove unchecked items.
			foreach ( $metrics as $metric ) {
				// Visit time and page time are same.
				if ( 'visit_time' === $metric['key'] ) {
					if ( ! in_array( 'page_time', $selected_metrics, true ) ) {
						continue;
					}
				} elseif ( ! in_array( $metric['key'], $selected_metrics, true ) ) {
					continue;
				}

				// Set metrics.
				$strings['metrics'][] = $metric;
			}

			// Translated strings for react widget.
			wp_localize_script(
				'wpmudev-dashboard-widget',
				'wdpI18n',
				$strings
			);
		}
	}

	/**
	 * Check if analytics widget can be shown.
	 *
	 * @since 4.11.6
	 *
	 * @return bool
	 */
	private function can_show_analytics_widget() {
		// Enabled metrics.
		$metrics_enabled = (array) WPMUDEV_Dashboard::$site->get_metrics_on_analytics();

		// Unique pageviews alone can not be used.
		$metrics_enabled = array_filter(
			$metrics_enabled,
			function ( $metric ) {
				return 'unique_pageviews' !== $metric;
			}
		);

		return (
			WPMUDEV_Dashboard::$api->is_analytics_allowed() // Only if analytics allowed.
			&& WPMUDEV_Dashboard::$settings->get( 'enabled', 'analytics' ) // Only if analytics enabled.
			&& ! empty( $metrics_enabled ) // Only if at least one metric is selected.
		);
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

	public function render_project( $pid, $other_pids = false, $message = false, $withmenu = false ) {
		$as_json = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( $as_json ) {
			ob_start();
		}

		$membership_type       = WPMUDEV_Dashboard::$api->get_membership_status();
		$membership_data       = WPMUDEV_Dashboard::$api->get_membership_data();
		$hide_row              = false;
		$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
		$is_hosted_third_party = WPMUDEV_Dashboard::$api->is_hosted_third_party();
		$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();

		$urls = $this->page_urls;
		$this->render(
			'sui/element-project-info',
			compact(
				'pid',
				'urls',
				'membership_type',
				'membership_data',
				'hide_row',
				'is_wpmudev_host',
				'is_standalone_hosting',
				'is_hosted_third_party'
			)
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

		$membership_type = WPMUDEV_Dashboard::$api->get_membership_status();
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
			// string is expected by WpOrg\Requests\Cookie class https://incsub.atlassian.net/browse/WDD-548 ( continuation of wp_remote_get )
			if ( ! is_string( $value ) ) {
				continue;
			}
			$cookies[] = new WP_Http_Cookie(
				array(
					'name'  => $name,
					'value' => $value,
				)
			);
		}

		/**
		 * Override default cookies for UI - get_admin_menu.
		 *
		 * @param array $cookies Default cookies.
		 *
		 * @since  4.11.29
		 *
		 */
		$cookies = apply_filters( "wpmudev_ui_get_admin_menu_cookies", $cookies );

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
		$updates = WPMUDEV_Dashboard::$settings->get_transient( 'update_plugins', false );

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
			! WPMUDEV_Dashboard::$settings->get( 'highlights_dismissed', 'flags' )
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
						// Install action if required.
						if ( ! empty( $_get_data['action'] ) && 'install' === $_get_data['action'] && $project->can_update ) {
							$redirect = $this->page_urls->plugins_url . '#install-pid=' . $project->pid;
						} else {
							$redirect = $this->page_urls->plugins_url . '#pid=' . $project->pid;
						}
						WPMUDEV_Dashboard::$ui->redirect_to( $redirect );
					}
				}
			}
		}

		if ( $is_logged_in ) {
			$data = WPMUDEV_Dashboard::$api->get_projects_data();
			// Show total number of available updates.
			$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );
			if ( is_array( $updates ) ) {
				foreach ( $updates as $id => $item ) {
					if ( 'plugin' === $item['type'] ) {
						// Skip addons.
						if ( ! empty( $data['projects'][ $id ]['is_plugin_addon'] ) ) {
							continue;
						}

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
			$membership_type       = WPMUDEV_Dashboard::$api->get_membership_status();
			$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
			$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();
			$has_hosted_access     = $is_wpmudev_host && ! $is_standalone_hosting && 'free' === $membership_type;

			if ( WPMUDEV_Dashboard::$utils->can_access_feature( 'plugins' ) || $has_hosted_access ) {
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
			}

			if ( WPMUDEV_Dashboard::$utils->can_access_feature( 'support' ) || $has_hosted_access ) {
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
			}

			do_action( 'wpmudev_dashboard_setup_menu', 'analytics' );
			$this->add_submenu(
				'analytics',
				__( 'WPMU DEV Analytics', 'wpmudev' ),
				__( 'Analytics', 'wpmudev' ),
				array( $this, 'render_analytics' ),
				$need_cap
			);

			if ( WPMUDEV_Dashboard::$utils->can_access_feature( 'whitelabel' ) ) {
				do_action( 'wpmudev_dashboard_setup_menu', 'whitelabel' );
				$this->add_submenu(
					'whitelabel',
					__( 'WPMU DEV Whitelabel', 'wpmudev' ),
					__( 'White Label', 'wpmudev' ),
					array( $this, 'render_whitelabel' ),
					$need_cap
				);
			}

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
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M1.91282 3.91087C1.63883 4.37887 1.49602 4.91528 1.50009 5.4611L1.50009 17.4622L3.70066 17.4622L3.70066 5.45906C3.70041 5.36562 3.71895 5.27313 3.75514 5.18736C3.79133 5.1016 3.84439 5.02441 3.91099 4.96063C4.06577 4.82307 4.26374 4.74734 4.46857 4.74734C4.67341 4.74734 4.87138 4.82307 5.02616 4.96063C5.09221 5.02474 5.14477 5.10204 5.1806 5.18776C5.21643 5.27347 5.23477 5.36581 5.2345 5.45906L5.2345 14.496C5.22425 14.8878 5.29258 15.2776 5.43525 15.6411C5.57793 16.0047 5.79191 16.3344 6.06393 16.6098C6.46926 17.0329 6.98871 17.3222 7.55557 17.4403C8.12243 17.5585 8.71081 17.5003 9.24513 17.273C9.77945 17.0458 10.2353 16.66 10.5542 16.1652C10.873 15.6703 11.0403 15.0891 11.0346 14.496L11.0346 5.45906C11.033 5.36654 11.0498 5.27465 11.0839 5.18898C11.118 5.1033 11.1687 5.02561 11.233 4.96063C11.3071 4.88829 11.3946 4.83207 11.4905 4.79536C11.5863 4.75865 11.6884 4.7422 11.7906 4.74701C11.8928 4.74148 11.9951 4.75759 12.091 4.79434C12.187 4.83109 12.2745 4.88769 12.3482 4.96063C12.4148 5.02441 12.4678 5.1016 12.504 5.18736C12.5402 5.27313 12.5587 5.36562 12.5585 5.45906L12.5585 14.496C12.5483 14.8876 12.6165 15.2772 12.7588 15.6407C12.9011 16.0042 13.1146 16.334 13.3859 16.6098C13.6579 16.8898 13.9828 17.1099 14.3408 17.2565C14.6987 17.4031 15.0821 17.4731 15.4674 17.4622C16.2593 17.4718 17.0239 17.1662 17.6005 16.6098C17.8904 16.345 18.1209 16.0189 18.2761 15.654C18.4313 15.2891 18.5075 14.894 18.4994 14.496L18.4994 2.50303L16.2969 2.50303L16.2969 14.496C16.2984 14.5885 16.2816 14.6804 16.2475 14.7661C16.2134 14.8518 16.1627 14.9295 16.0984 14.9944C15.9437 15.132 15.7457 15.2077 15.5409 15.2077C15.336 15.2077 15.1381 15.132 14.9833 14.9944C14.917 14.9304 14.8641 14.8532 14.8279 14.7675C14.7918 14.6818 14.773 14.5894 14.7729 14.496L14.7729 5.45906C14.7829 5.06751 14.7146 4.67801 14.5723 4.3145C14.43 3.951 14.2167 3.62117 13.9455 3.34529C13.6739 3.06817 13.3502 2.85045 12.9942 2.70533C12.6381 2.56021 12.257 2.49069 11.8739 2.501C11.0807 2.48839 10.3134 2.79094 9.73287 3.34529C9.44323 3.61043 9.21281 3.93653 9.05734 4.30133C8.90187 4.66613 8.825 5.06103 8.83201 5.45906L8.83201 14.496C8.81366 14.6717 8.73258 14.8343 8.60437 14.9524C8.47617 15.0705 8.30988 15.1359 8.13751 15.1359C7.96514 15.1359 7.79885 15.0705 7.67065 14.9524C7.54244 14.8343 7.46136 14.6717 7.44301 14.496L7.44301 5.45906C7.44762 4.91289 7.30403 4.37616 7.0283 3.90883C6.76827 3.45456 6.38493 3.08769 5.92504 2.85296C5.47483 2.62153 4.97815 2.50102 4.47453 2.50102C3.9709 2.50102 3.47423 2.62153 3.02402 2.85296C2.56072 3.08665 2.1744 3.45445 1.91282 3.91087Z" fill="#A7AAAD"/>
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

		$auth_verify_nonce = wp_verify_nonce( ( isset( $_REQUEST['auth_nonce'] ) ? $_REQUEST['auth_nonce'] : '' ), 'auth_nonce' );

		// First login redirect is done.
		if ( ! WPMUDEV_Dashboard::$settings->get( 'redirected_v4', 'flags' ) ) {
			WPMUDEV_Dashboard::$settings->set( 'redirected_v4', true, 'flags' );
		}

		if ( ! empty( $_GET['clear_key'] ) ) {// wpcs csrf ok.
			// User requested to log-out.
			WPMUDEV_Dashboard::$site->logout();
		} elseif ( isset( $_REQUEST['is_multi_auth'] ) && 1 === (int) $_REQUEST['is_multi_auth'] && ! empty( $_REQUEST['user_apikey'] ) ) {
			// nonce verifier.
			if ( ! $auth_verify_nonce ) {
				// User has no permission to view the page.
				$this->render_with_sui_wrapper( 'sui/no_access' );

				return;
			}
			$url = add_query_arg(
				array(
					'view'       => 'team-selection',
					'key'        => trim( $_REQUEST['user_apikey'] ),
					'auth_nonce' => $_REQUEST['auth_nonce'],
				),
				$this->page_urls->dashboard_url
			);
			$this->redirect_to( $url );
		} elseif ( ! empty( $_REQUEST['set_apikey'] ) ) {
			// nonce verifier.
			if ( ! $auth_verify_nonce ) {
				// User has no permission to view the page.
				$this->render_with_sui_wrapper( 'sui/no_access' );

				return;
			}
			$url = add_query_arg(
				array(
					'view'       => 'sync',
					'key'        => trim( $_REQUEST['set_apikey'] ),
					'auth_nonce' => $_REQUEST['auth_nonce'],
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
			$type            = WPMUDEV_Dashboard::$api->get_membership_status();
			$projects        = WPMUDEV_Dashboard::$api->get_membership_projects();
			$active_projects = $this->get_total_active_projects( $data['projects'], false );
			// We need this only for free memberships.
			$free_plugins   = 'free' === $type ? WPMUDEV_Dashboard::$site->get_installed_free_projects() : array();
			$projects_nr    = $this->get_total_projects( $data['projects'] );
			$update_plugins = 0;
			$staff_login    = WPMUDEV_Dashboard::$api->remote_access_details();

			// tools settings
			$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
			$analytics_allowed   = WPMUDEV_Dashboard::$api->is_analytics_allowed();
			$whitelabel_allowed  = WPMUDEV_Dashboard::$api->is_whitelabel_allowed();
			$analytics_enabled   = WPMUDEV_Dashboard::$settings->get( 'enabled', 'analytics' );
			$membership_data     = WPMUDEV_Dashboard::$settings->get( 'membership_data' );
			$hub_site_id         = WPMUDEV_Dashboard::$api->get_site_id();

			$total_visits   = 0;
			$tickets_hidden = WPMUDEV_Dashboard::$api->is_tickets_hidden();

			// Get visits.
			if ( $analytics_enabled && WPMUDEV_Dashboard::$api->is_analytics_allowed() ) {
				$visits = WPMUDEV_Dashboard::$api->analytics_stats_overall();
				if ( ! empty( $visits['overall']['totals']['visits'] ) ) {
					$total_visits = $visits['overall']['totals']['visits'];
				}
			}

			// Show total number of available updates.
			$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );
			if ( is_array( $updates ) ) {
				foreach ( $updates as $id => $item ) {
					if ( 'plugin' === $item['type'] ) {
						// Skip addons.
						if ( ! empty( $data['projects'][ $id ]['is_plugin_addon'] ) ) {
							continue;
						}

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
				compact(
					'data',
					'member',
					'urls',
					'type',
					'licensed_projects',
					'projects_nr',
					'active_projects',
					'free_plugins',
					'update_plugins',
					'staff_login',
					'whitelabel_settings',
					'analytics_enabled',
					'analytics_allowed',
					'whitelabel_allowed',
					'total_visits',
					'membership_data',
					'tickets_hidden',
					'hub_site_id'
				)
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
		$classes       .= ' wpmudevdash wpmud-' . $current_module . ' ' . WPMUDEV_Dashboard::$sui_version;

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
				'no_plugin_activated'                 => __( 'You don\'t have any WPMU DEV plugins installed and activated.', 'wpmudev' ),
				'no_plugin_deactivated'               => __( 'You don\'t have any deactivated WPMU DEV plugins.', 'wpmudev' ),
				'no_plugin_updates'                   => __( 'There are no WPMU DEV plugin updates available.', 'wpmudev' ),
				'plugins_active'                      => __( 'Active', 'wpmudev' ),
				'plugins_not_installed'               => __( 'Not installed', 'wpmudev' ),
				'plugins_cannot_delete'               => __( 'The following plugins are either active or not installed and cannot be deleted:', 'wpmudev' ),
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

		// Check if hub free services active.
		$free_services_active = WPMUDEV_Dashboard::$compatibility->is_free_services_active();

		// WPMUDEV hosting.
		$membership_type       = WPMUDEV_Dashboard::$api->get_membership_status();
		$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
		$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();
		$has_hosted_access     = $is_wpmudev_host && ! $is_standalone_hosting && 'free' === $membership_type;

		$this->render(
			'sui/header',
			compact(
				'page_title',
				'is_logged_in',
				'url_dash',
				'url_logout',
				'profile',
				'url_support',
				'documentation_url',
				'has_hosted_access',
				'free_services_active'
			)
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
		echo '<div id="wpmudui-analytics-app"></div>';
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
		$membership_type = WPMUDEV_Dashboard::$api->get_membership_status();
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
		$type            = WPMUDEV_Dashboard::$api->get_membership_status();
		$projects        = WPMUDEV_Dashboard::$api->get_membership_projects();

		// handles multiple snapshot project.
		$data = $this->handle_snapshot_v4( $data );

		// Show total number of available updates.
		$updates = WPMUDEV_Dashboard::$settings->get( 'updates_available' );
		if ( is_array( $updates ) ) {
			foreach ( $updates as $id => $item ) {
				if ( 'plugin' === $item['type'] ) {
					// Skip addons.
					if ( ! empty( $data['projects'][ $id ]['is_plugin_addon'] ) ) {
						continue;
					}

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
		$membership_type = WPMUDEV_Dashboard::$api->get_membership_status();
		$notes           = WPMUDEV_Dashboard::$settings->get( 'staff_notes', 'general' );
		$access          = WPMUDEV_Dashboard::$settings->get( 'remote_access' );
		$membership_data = WPMUDEV_Dashboard::$settings->get( 'membership_data' );
		$tickets_hidden  = WPMUDEV_Dashboard::$api->is_tickets_hidden();

		if ( empty( $access['logins'] ) || ! is_array( $access['logins'] ) ) {
			$access_logs = array();
		} else {
			$access_logs = $access['logins'];
		}

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-support' );

		$this->render_with_sui_wrapper(
			'sui/support',
			compact(
				'profile',
				'data',
				'urls',
				'staff_login',
				'notes',
				'access_logs',
				'membership_data',
				'membership_type',
				'tickets_hidden'
			)
		);
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
		$membership_type     = WPMUDEV_Dashboard::$api->get_membership_status();
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		$analytics_enabled   = WPMUDEV_Dashboard::$settings->get( 'enabled', 'analytics' );
		$analytics_allowed   = WPMUDEV_Dashboard::$api->is_analytics_allowed();
		$analytics_role      = WPMUDEV_Dashboard::$settings->get( 'role', 'analytics' );
		$analytics_role      = empty( $analytics_role ) ? 'administrator' : $analytics_role;
		$analytics_metrics   = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();
		$membership_data     = WPMUDEV_Dashboard::$settings->get( 'membership_data' );

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
		$analytics_enabled   = WPMUDEV_Dashboard::$settings->get( 'enabled', 'analytics' );
		$analytics_role      = WPMUDEV_Dashboard::$settings->get( 'role', 'analytics' );
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
		$auto_update             = WPMUDEV_Dashboard::$settings->get( 'autoupdate_dashboard', 'flags' );
		$enable_sso              = WPMUDEV_Dashboard::$settings->get( 'enabled', 'sso' );
		$membership_type         = WPMUDEV_Dashboard::$api->get_membership_status();
		$enable_auto_translation = WPMUDEV_Dashboard::$settings->get( 'enable_auto_translation', 'flags' );
		$translation_update      = WPMUDEV_Dashboard::$settings->get( 'translation_updates_available' );
		$keep_data               = WPMUDEV_Dashboard::$settings->get( 'uninstall_keep_data', 'flags' );
		$preserve_settings       = WPMUDEV_Dashboard::$settings->get( 'uninstall_preserve_settings', 'flags' );

		/**
		 * Custom hook to display own notifications inside Dashboard.
		 */
		do_action( 'wpmudev_dashboard_notice-settings' );

		$this->render_with_sui_wrapper(
			'sui/settings',
			compact(
				'member',
				'urls',
				'allowed_users',
				'available_users',
				'auto_update',
				'enable_sso',
				'membership_type',
				'translation_update',
				'enable_auto_translation',
				'keep_data',
				'preserve_settings'
			)
		);
	}

	/**
	 * Display the header that tells the user to upgrade their membership.
	 *
	 * @param string $reason The reason why the user needs to upgrade.
	 *
	 * @since  4.9.0
	 */
	protected function render_upgrade_header( $reason, $licensed_projects ) {
		$is_logged_in = WPMUDEV_Dashboard::$api->has_key();
		$urls         = $this->page_urls;
		$user         = wp_get_current_user();

		$username = $user->user_firstname;
		if ( empty( $username ) ) {
			$username = $user->display_name;
		}
		if ( empty( $username ) ) {
			$username = $user->user_login;
		}
		$membership_data = WPMUDEV_Dashboard::$settings->get( 'membership_data' );
		$this->render(
			'sui/header-no-access',
			compact( 'is_logged_in', 'urls', 'username', 'reason', 'licensed_projects', 'membership_data' )
		);
	}

	/**
	 * Display the header that tells the user to upgrade their membership.
	 *
	 * @since 4.11.9
	 */
	protected function render_switch_free_notice( $campaign = '' ) {
		$this->render(
			'sui/header-switch-notice',
			array(
				'campaign' => $campaign,
				'urls'     => $this->page_urls,
			)
		);
	}
}