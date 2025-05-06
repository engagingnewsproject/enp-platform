<?php
/////////////////////////////////////////////////////////////////////////
/* -------- WPMU DEV Dashboard Notice - Aaron Edwards (Incsub) ------- */
/* This provides notices of available updates for our premium products */
if ( ! class_exists( 'WPMUDEV_Dashboard_Notice4' ) ) {
	class WPMUDEV_Dashboard_Notice4 {
		var $version        = '4.2.5';
		var $screen_id      = false;
		var $product_name   = false;
		var $product_update = false;
		var $theme_pack     = 128;
		var $server_url     = 'https://wpmudev.com/api/dashboard/v1/';
		var $update_count   = 0;

		/**
		 * Class construct.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'plugins_loaded', array( $this, 'remove_older' ), 5 );
		}

		/**
		 * Remove older notice actions.
		 *
		 * @return void
		 */
		public function remove_older() {
			global $WPMUDEV_Dashboard_Notice3;

			// Remove 3.0 notices.
			if ( is_object( $WPMUDEV_Dashboard_Notice3 ) ) {
				remove_action( 'init', array( $WPMUDEV_Dashboard_Notice3, 'init' ) );
				remove_action( 'plugins_loaded', array( $WPMUDEV_Dashboard_Notice3, 'init' ) );
			} elseif ( method_exists( 'WPMUDEV_Dashboard_Notice3', 'init' ) ) {
				// If class is not in global (some projects included inside a method), we have to use a hacky way to remove the filter.
				$this->deregister_hook( 'init', 'WPMUDEV_Dashboard_Notice3', 'init', 10 );
				$this->deregister_hook( 'plugins_loaded', 'WPMUDEV_Dashboard_Notice3', 'init', 10 );
			}

			// Remove version 2.0.
			if ( method_exists( 'WPMUDEV_Dashboard_Notice', 'init' ) ) {
				$this->deregister_hook( 'init', 'WPMUDEV_Dashboard_Notice', 'init', 10 );
				$this->deregister_hook( 'plugins_loaded', 'WPMUDEV_Dashboard_Notice', 'init', 10 );
			}

			// Remove version 1.0.
			remove_action( 'admin_notices', 'wdp_un_check', 5 );
			remove_action( 'network_admin_notices', 'wdp_un_check', 5 );
		}

		/**
		 * Adapted from: https://github.com/herewithme/wp-filters-extras/ - Copyright 2012 Amaury Balmer - amaury@beapi.fr
		 *
		 * @param string $hook_name    Hook name.
		 * @param string $class_name   Class name.
		 * @param string $method_name  Method name.
		 * @param int    $priority     Priority.
		 *
		 * @return void
		 */
		private function deregister_hook( $hook_name, $class_name, $method_name, $priority ) {
			global $wp_filter;

			// Take only filters on right hook name and priority.
			if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
				return;
			}

			// Loop on filters registered.
			foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
				// Test if filter is an array ! (always for class/method).
				if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
					// Test if object is a class, class and method is equal to param !
					if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) == $class_name && $filter_array['function'][1] == $method_name ) {
						if ( class_exists( 'WP_Hook' ) ) { // Introduced in WP 4.7 https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/.
							unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
						} else {
							unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
						}
						return;
					}
				}
			}
		}

		/**
		 * Init method.
		 *
		 * @return void
		 */
		public function init() {
			global $wpmudev_un;

			if ( class_exists( 'WPMUDEV_Dashboard' ) || ( isset( $wpmudev_un->version ) && version_compare( $wpmudev_un->version, '3.4', '<' ) ) ) {
				return;
			}

			// Schedule update cron on main site only.
			if ( is_main_site() ) {
				if ( ! wp_next_scheduled( 'wpmudev_scheduled_jobs' ) ) {
					wp_schedule_event( time(), 'twicedaily', 'wpmudev_scheduled_jobs' );
				}

				add_action( 'wpmudev_scheduled_jobs', array( $this, 'updates_check' ) );
			}
			add_action( 'delete_site_transient_update_plugins', array( $this, 'updates_check' ) ); // Refresh after upgrade/install.
			add_action( 'delete_site_transient_update_themes', array( $this, 'updates_check' ) ); // Refresh after upgrade/install.

			if ( is_admin() && current_user_can( 'install_plugins' ) ) {

				add_action( 'site_transient_update_plugins', array( &$this, 'filter_plugin_count' ) );
				add_action( 'site_transient_update_themes', array( &$this, 'filter_theme_count' ) );
				add_filter( 'plugins_api', array( $this, 'filter_plugin_info' ), 101, 3 ); // Run later to work with bad auto update plugins.
				add_filter( 'themes_api', array( $this, 'filter_plugin_info' ), 101, 3 ); // Run later to work with bad auto update plugins.
				add_action( 'load-plugins.php', array( $this, 'filter_plugin_rows' ), 21 ); // Make sure it runs after WordPress.
				add_action( 'load-themes.php', array( $this, 'filter_plugin_rows' ), 21 ); // Make sure it runs after WordPress.
				add_action( 'core_upgrade_preamble', array( $this, 'disable_checkboxes' ) );
				add_action( 'activated_plugin', array( $this, 'set_activate_flag' ) );
				add_action( 'wp_ajax_wdpun-changelog', array( $this, 'popup_changelog_ajax' ) );
				add_action( 'wp_ajax_wdpun-dismiss', array( $this, 'dismiss_ajax' ) );

				// If dashboard is installed but not activated.
				if ( file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
					if ( ! get_site_option( 'wdp_un_autoactivated' ) ) {
						// Include plugin API if necessary.
						if ( ! function_exists( 'activate_plugin' ) ) {
							require_once ABSPATH . 'wp-admin/includes/plugin.php';
						}
						$result = activate_plugin( '/wpmudev-updates/update-notifications.php', network_admin_url( 'admin.php?page=wpmudev' ), is_multisite() );
						if ( ! is_wp_error( $result ) ) { // If autoactivate successful don't show notices.
							update_site_option( 'wdp_un_autoactivated', 1 );
							return;
						}
					}

					add_action( 'admin_print_styles', array( $this, 'notice_styles' ) );
					add_action( 'admin_print_footer_scripts', array( $this, 'notice_scripts' ) );
					add_action( 'all_admin_notices', array( $this, 'activate_notice' ), 5 );
				} else { // Dashboard not installed at all.
					if ( get_site_option( 'wdp_un_autoactivated' ) ) {
						update_site_option( 'wdp_un_autoactivated', 0 ); // Reset flag when dashboard is deleted.
					}
					add_action( 'admin_print_styles', array( $this, 'notice_styles' ) );
					add_action( 'admin_print_footer_scripts', array( $this, 'notice_scripts' ) );
					add_action( 'all_admin_notices', array( $this, 'install_notice' ), 5 );
				}
			}
		}

		/**
		 * Check if we're on an allowed page.
		 *
		 * @return bool
		 */
		private function is_allowed_screen() {
			global $wpmudev_notices;

			$screen = get_current_screen();
			if ( $screen && is_object( $screen ) ) {
				$this->screen_id = $screen->id;
			}

			// Show special message right after plugin activation.
			if ( in_array( $this->screen_id, array( 'plugins', 'plugins-network' ) ) && ( isset( $_GET['activate'] ) || isset( $_GET['activate-multi'] ) ) ) {
				$activated = get_site_option( 'wdp_un_activated_flag' );
				if ( false === $activated ) {
					$activated = 1;
				} // On first encounter of new installed notice show.
				if ( $activated ) {
					if ( $activated >= 2 ) {
						update_site_option( 'wdp_un_activated_flag', 0 );
					} else {
						update_site_option( 'wdp_un_activated_flag', 2 );
					}

					return true;
				}
			}

			// Check dismiss flag.
			$dismissed = get_site_option( 'wdp_un_dismissed' );
			if ( $dismissed && $dismissed > strtotime( '-1 week' ) ) {
				return false;
			}

			// Always show on certain core pages if updates are available.
			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( is_array( $updates ) && count( $updates ) ) {
				$this->update_count = count( $updates );
				if ( in_array( $this->screen_id, array( 'update-core', 'update-core-network' ) ) ) {
					return true;
				}
			}

			// Check our registered plugins for hooks.
			if ( isset( $wpmudev_notices ) && is_array( $wpmudev_notices ) ) {
				foreach ( $wpmudev_notices as $product ) {
					if ( isset( $product['screens'] ) && is_array( $product['screens'] ) && in_array( $this->screen_id, $product['screens'] ) ) {
						$this->product_name = $product['name'];
						// If this plugin needs updating flag it.
						if ( isset( $product['id'] ) && isset( $updates[ $product['id'] ] ) ) {
							$this->product_update = true;
						}

						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Auto install URL.
		 *
		 * @return string
		 */
		private function auto_install_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url( $function( 'update.php?action=install-plugin&plugin=install_wpmudev_dash' ), 'install-plugin_install_wpmudev_dash' );
		}

		/**
		 * Activate URL.
		 *
		 * @return string
		 */
		private function activate_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url( $function( 'plugins.php?action=activate&plugin=wpmudev-updates%2Fupdate-notifications.php' ), 'activate-plugin_wpmudev-updates/update-notifications.php' );
		}

		/**
		 * Installation notice.
		 *
		 * @return void
		 */
		public function install_notice() {
			if ( ! $this->is_allowed_screen() ) {
				return;
			}
			?>

			<div class="notice wdpun-notice" style="display: none;">
				<input type="hidden" name="msg_id" value="<?php esc_attr_e( 'install', 'wpmudev' ); ?>" />
				<div class="wdpun-notice-logo"></div>
				<div class="wdpun-notice-message">
					<?php
					if ( $this->product_name ) {
						if ( $this->product_update ) {
							printf( __( 'Important updates are available for <strong>%s</strong>. Install the free WPMU DEV Dashboard plugin now for updates and support!', 'wpmudev' ), esc_html( $this->product_name ) );
						} else {
							printf( __( '<strong>%s</strong> is almost ready - install the free WPMU DEV Dashboard plugin for updates and support!', 'wpmudev' ), esc_html( $this->product_name ) );
						}
					} elseif ( $this->update_count ) {
						esc_html_e( 'Important updates are available for your WPMU DEV plugins/themes. Install the free WPMU DEV Dashboard plugin now for updates and support!', 'wpmudev' );
					} else {
						esc_html_e( 'Almost ready - install the free WPMU DEV Dashboard plugin for updates and support!', 'wpmudev' );
					}
					?>
				</div><!-- end wdpun-notice-message -->
				<div class="wdpun-notice-cta">
					<a href="<?php echo esc_url( $this->auto_install_url() ); ?>" class="wdpun-button wdpun-button-small">
						<?php esc_html_e( 'Install Plugin', 'wpmudev' ); ?>
					</a>
					<button class="wdpun-button wdpun-button-notice-dismiss" data-msg="<?php esc_attr_e( 'Saving...', 'wpmudev' ); ?>">
						<?php esc_html_e( 'Dismiss', 'wpmudev' ); ?>
					</button>
				</div><!-- end wdpun-notice-cta -->
			</div><!-- end notice wdpun-notice -->
			<?php
		}

		/**
		 * Activate notice.
		 *
		 * @return void
		 */
		public function activate_notice() {
			if ( ! $this->is_allowed_screen() ) {
				return;
			}
			?>

			<div class="notice wdpun-notice" style="display: none;">
				<input type="hidden" name="msg_id" value="<?php esc_attr_e( 'activate', 'wpmudev' ); ?>" />
					<div class="wdpun-notice-logo"><span></span></div>
				<div class="wdpun-notice-message">
					<?php
					if ( $this->product_name ) {
						if ( $this->product_update ) {
							printf( __( 'Important updates are available for <strong>%s</strong>. Activate the WPMU DEV Dashboard to update now!', 'wpmudev' ), esc_html( $this->product_name ) );
						} else {
							printf( __( 'Just one more step to enable updates and support for <strong>%s</strong>!', 'wpmudev' ), esc_html( $this->product_name ) );
						}
					} elseif ( $this->update_count ) {
						esc_html_e( 'Important updates are available for your WPMU DEV plugins/themes. Activate the WPMU DEV Dashboard to update now!', 'wpmudev' );
					} else {
						esc_html_e( "Just one more step - activate the WPMU DEV Dashboard plugin and you're all done!", 'wpmudev' );
					}
					?>
				</div><!-- end wdpun-notice-message -->
				<div class="wdpun-notice-cta">
					<a href="<?php echo esc_url( $this->activate_url() ); ?>" class="wdpun-button wdpun-button-small">
						<?php esc_html_e( 'Activate WPMU DEV Dashboard', 'wpmudev' ); ?>
					</a>
					<button class="wdpun-button wdpun-button-notice-dismiss" data-msg="<?php esc_attr_e( 'Saving...', 'wpmudev' ); ?>">
						<?php esc_html_e( 'Dismiss', 'wpmudev' ); ?>
					</button>
				</div><!-- end wdpun-notice-cta -->
			</div><!-- end notice wdpun-notice -->
			<?php
		}

		/**
		 * Notice styles.
		 *
		 * @return void
		 */
		public function notice_styles() {
			if ( ! $this->is_allowed_screen() ) {
				return;
			}
			?>
			<style type="text/css" media="all">
				.cf:after{content:"";display:table;clear:both}@media only screen and (min-width:1200px){.hide-to-large{display:none}}@media only screen and (min-width:1140px){.hide-to-desktop{display:none}}.wrap>.wdpun-notice.notice,.wrap #header>.wdpun-notice.notice{width:100%}.wrap #header>.wdpun-notice.notice{box-shadow:none}.wdpun-notice *,.wdpun-notice *:after,.wdpun-notice *:before{box-sizing:border-box}.wdpun-notice.notice{background:#fff;border:1px solid #e5e5e5;border-radius:6px;box-shadow:0 1px 1px 0 rgb(0 0 0 / .05);clear:both;display:block;font:400 13px/20px "Open Sans",Arial,sans-serif;overflow:hidden;margin:10px 20px 20px 0;min-height:80px;padding:0;position:relative;text-align:center;z-index:1}.wdpun-notice.notice.loading:before{background-color:rgb(255 255 255 / .7);bottom:0;content:attr(data-message);font-size:22px;font-weight:600;left:0;line-height:80px;position:absolute;right:0;text-align:center;top:0;z-index:5}.wdpun-notice.notice.loading>div{-webkit-filter:blur(2px);filter:blur(2px)}.wdpun-notice-logo{background-color:#fff0;background-image:url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDYiIGhlaWdodD0iNDYiIHZpZXdCb3g9IjAgMCA0NiA0NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CjxyZWN0IHdpZHRoPSI0NiIgaGVpZ2h0PSI0NiIgZmlsbD0idXJsKCNwYXR0ZXJuMF8yOTY0XzM5MCkiLz4KPGRlZnM+CjxwYXR0ZXJuIGlkPSJwYXR0ZXJuMF8yOTY0XzM5MCIgcGF0dGVybkNvbnRlbnRVbml0cz0ib2JqZWN0Qm91bmRpbmdCb3giIHdpZHRoPSIxIiBoZWlnaHQ9IjEiPgo8dXNlIHhsaW5rOmhyZWY9IiNpbWFnZTBfMjk2NF8zOTAiIHRyYW5zZm9ybT0ic2NhbGUoMC4wMDQxNjY2NykiLz4KPC9wYXR0ZXJuPgo8aW1hZ2UgaWQ9ImltYWdlMF8yOTY0XzM5MCIgd2lkdGg9IjI0MCIgaGVpZ2h0PSIyNDAiIHhsaW5rOmhyZWY9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBUEFBQUFEd0NBWUFBQUErVmVtU0FBQUFDWEJJV1hNQUFCWWxBQUFXSlFGSlVpVHdBQUFBQVhOU1IwSUFyczRjNlFBQUFBUm5RVTFCQUFDeGp3djhZUVVBQUJQNVNVUkJWSGdCN2QzUGIxUlhsc0R4VXhqakh4Q3dDVUlZbzBtUkh3S3hHQ0NOTk5sUk1JdWVWVU15RXFNWlJjR2dMQ0loaGFUL2dOak9aalNianYwUEpDYWJtY21DT05uTllvTEpLcXRnendJaEpkTlVXbUNuQWNWMkF0allnUHVjNGpvcEN2dmRXNi9LNVdmZjcwY3FWZGwxeTg5VjljNDc5OWU3VHdRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFLeW1uR0RONlZDdHJhMkg5R0UrbDhzZDB0dTJoWVdGdlAxc3ordlBIVmFzOG5WYXBxalBUZW45bE4zcnI4YjA4YVRleG1ablowZW5sR0JOSVlEWGdGMjdkaFUwNEE3cXJhREJaZ0dibDVWUjFOdW9ibVBFZ3ZySEgzOGNFV1FhQVp4QmxtSGIydHBPNjhPVGxtRmxpV3phSUphUlIvUi9HSDcwNk5GbERlaWlJRk1JNEl5b0NOcUNaSkJsNWcwYk5nd1J6TmxCQUs4eXF4NXJVSnpYaHdWWnZVeWJ4dERqeDQ4dlVNMWVYUVR3S25IdDJ0NnNadHNxWE5IM01Iano1czBMZ29ZamdCdXNxNnZMcXNnZmllc3hYa2V1Ni92cUo1QWJpd0J1a0hXVWNYMnVhOVg2TEZYcnhpQ0FWNWdHcm8zVmZsTHZ3SFZqdWpiazg0UGVSbTFjVjl2U1JlMWdtdEl4M2FuS01WMDNkdHpSMU5UVW9XVTZiRmhLZjcxWDcxK1FKN1dCUTFKZjFrYnVwN05yWlJIQUswaXJ5K2MxUVBxa0RwMVQxZ09zZit1eUJzVkl2U2RkOVBYMXljREFRR2x5aUI0RWJLejVhSjBPT0pONkd4d2ZIKzhYckFnQ2VBWFVLZXZhakNuTHNCYzBZSWNiT1V1cUxLQlBha0NmMEYrZGxOcFl0Zm80MmJqK0NPQTZxelhyV3FiVnUrR1ptWmtMV1pqYVdCN00rcjVPMTNCUXNpbWJIMDVNVEF3STZvWUFyaE5yWTI3ZXZMbFhkOUwzSkozTWo2c2VPSEJBcHFlbkQ3bjNlRnBTME5kK3BFSDhSMEZkRU1CMVlGVm1yV3ArTHVrNmd0WmNaMCtoVUpCcjE2N1plKzZUZElGTWxicE9DT0FhZFhkM1cwYXk0TTFYOHpwM3dzQ1p0YndUMXhqSUJIRWRFTUExY0pNeVBwRXEycnMyL09NQ2QwVFdpWHcrTDlyUmRrdy9pNCtyUEZQSzJzVm50VW85TEVpbFNaQ0tabDdMT1ArbHQ5WXFYdGF2Tyt2cmQrL2VMY282WW4xdDlwNTI3Tmd4cUdQTWxoUUtnUzl0MDREL0YrMDdLT3JyeHdSVkl3T25ZTUdybVdNb3RMeGxYYTFtdm43ejVzMVJXZWRjdGRvbWlIeFZSVFplME9xMDFVcVlobG1sRFlLcXBBamVRUjBTT2h4RDhKcVJrUkY1NTUxM3JyZTB0Qnkya3h3Q1g1YlRBOXduMmhtWXFtYzdabVRnS3JnMjcrZWg1VFY0MzQ5NTNQUGxsMStXZS9mdXZhK2YyWjhDWDZJZjJjSWJ0SW5ERWNDQjNGRFJGUW5yc0pyU0t1SHJUT2ovZGV6NHNINGVGd09yMUpQejgvUEhiOSsrSFVXTnBWWlVvUU80NEwwa0FjRnI3VjNkV1E4VHZFOWN2WHBWWG5ubGxTdjZ1UnkzenliZ0paM056YzBYN1RNWGVCSEFIamJEeWdWdjNsZldEUkVkWTJ6emFkWXUzcjkvLy9VcWduaXZmdVlYN2JNWEpDS0FQV3g2cEJDOE5Vc1J4SWZiMnRvK0VDUmlIRGlCblppZ2QzMitjZ1J2bUdLeEtFZU9ISm02YytmT2wvcmpDYmQrOWJMMCtkZTJiTmt5cldQRTN3aVdSQ2ZXTWtJN3JRamU2aTJPRmV2bis2MzQreFVtdFUvaFZUN2ZwVkdGWGtab3A1Vk4wR0RucW81VnAxdGJXMjB1OUJzQnhUdHRpcVpnU1ZTaGw3Qjc5MjVyOTNwUFlyZHgzdkh4Y2NZc1U3RHBsMXJMS2M3TnpmMnNBZnI3cExJMi9FUlZlbWxVb1N0VVVYVWVuSmlZU0h2dUw1dzllL2JrN0J4aHZaMzNGS1VxdlFTcTBCVkNWdE93ZHUvTXpFeWZvR1p2di8zMmdvNzc5Z2YwVEZPVlhnSVp1SXk3U3NJbFh6bk5CSHZKQlBXek9GdExnL2hiVDlFRmR3N3hpS0NFQUM2amJkL3I0aC96N2RkMmI1L1V5QzN6ZW5MeDhxQmxUNVdXaVYySitjQzJ6ZmIyZGx0MXNsQzVUVnZ0TWsxZ1dCWDQwYU5IdGxEOXdlWEsyUFdVZkF1KzJ6bkYyaDd1MDRlOVNlWDBmNytrbjgxeFFRa0I3SFIzZC9mb3p2RkpVaG1yNXVuT3MxZHFVTVVDN3pZOE5XeHQ3VnF6dlZzbDh6MWJsRTZTbXdkRitXMXRycUlFME1ETFBYanc0Q3ZQK3drNjZPbDNrTk50Lzlrelo1b1RIc3JRQm5aMHArZ05LSE5HVXJMc3B4bitjNnVpQjY3c1dBbzZLMjlCTHluWlpCVHJsTk8vWloxRXZtR3h2Tjc2dFB4WGR1YVZOSmkyaFMwNHozcUs1YW80dTJuZEk0RGxTZllWZjlWNUtHM2J5ektnVmwydFp6dE5VT1JkRUZkOXJxeStyNDkwWjdmVEdhdWRVMnduNUY5MHcya05Zek8xdEZreDRwYldUWkt2NWFDMm5oREFVc3FzdmlFTTY3aEtkWFdCc2pPWjhsSURhMGRXczlOYThOV3d4SzJ4NWxWdm96T3hWc2xEc3pEenBJVUFGaGNVdnVWZ2g5SzJROTJpZDNtcEExdTFJdVFNSFZzcFV3TG1jQWV3UVBtNGtXY0YyU3d0M1daUkh5WjJlbGt6aEN4TUFOdU8wT01ya3piN1d0Vzh6aGMxczZxNE42dnEvL3VSMUU5bnlEYnJhZCsrZlFzQm43a3R3L091UkM3cUFIYTlzNzYyWmVyc3F6dmhTcXp4bEZqZHQreWJxLzhsVE4vTlloWldoZGpQR1k0NmdKdWFtZ3ErTXZQejg2RUxzejNGSFJ3S3ZuSnVnZmRCZHl1S1gwZFMxVkgvUmxDYjFYVVVEUVYwR0pXMmFWY3VsQVpxYVdteExPd0w0STYydHJZZWlWalVBZXpMa0xaenAxMmJ5U1pvQkJTemRhS1AyWnhxZDdNeFptOTFQZWx2MjZWQkpaa0ZobTN6bUk3Tm50RkFPUmF3VGF1dUZxU0JBbnVrcmFQdER4S3hhQU00SkVOV3MzeHNKVjhBVzdaZGFuS0QrOTJVSk12TDh0djFWU21mR2c2elFObTBhVk4vUUNiK08ybXdqUnMzTHVqZGwwbGw3RHVNdVJvZGJRQ0hWSjluWjJlL2tKVlRYTzRKbTBvcENTcW1RVmJ5clhMeGRlWHZIang0WUwvL1FUTG16VGZmdEtyMGtIZ09hREZYbzZNTllBMlNFNTRpWDJUaCtyd3hzMnNUaTd2UWVVS3hxS3ZSTWJlQkMwbFBhanN4ZUFGM3JCd2R3bHJROXZlblNXVUMreHZXcFNnRDJQWGlKbFkxVjdqNmpFQ25UcDJ5T2RLK0V4YzZZcDNVRVdVQUIzUXdqVkI5em9heWF2UklVcmxZczNDc0FYelU4L3hsUVpZc2VMNlRuTzg3WGE5aWJRTVhrcDYwazlzRm1hTGZpZStnZWxBaUZGMEE1L041YS92NjJyK3BKbTlnWmZUMDlOaWtEdDkza285eFBEaTZBTmJnOUxXVlJtbi9ab3UxZytmbjUrMDdLU2FWYS9SMHp5eUlMb0FET2pzeU42RUJwZUMwdThRczNOVFVsSmZJeEJqQTNpVmpCWmtUTUZ2TTF0T0tyaDBjWXllVzcwc3VDakpuNjlhdE5pOTZMS21NWjRycHVoUmRBR3VHOVdWZ09yQXlhSEp5MHI2YnhMNEpmVDR2a2FFS1hlSFJvMGQwWUdWUVoyZm40a24reS9Jc1I3c3V4VmlGVGd4ZzdRaUpMb0FuSmlac0lUbkpzcDA3ZDNKd1hVS01BWnhQZWxLSG1kaEpNc2l1S2F3OTBiN3ZobkhnMkRFR25FMkxjNkk5eFFoZ0FHc0hBUXlzWVFRd3NJYkZHTUNKN2FqWTF4bk9LdGNHNXJ1cFFBQlgwSjVPZHBJTXNzWGVkWVRBOTkwVUpUSlVvU3ZvV0NNQm5FRzNidDJ5TVhyZmR4UGRDRUtNVXltTFNjOXYyTERoQllsUUxwZnRhNzNmdjMvZmUzRDFUYlZjajJMTXdJbW5DMjdjdUxGVEl0UFYxWlh0NkpYUzkrSTlGVlNmbjViSVJCZkFtbUVUajlLUEh6K09kb25TTE5QMmJ5NmdkalFta1lreEEvdXVlcENYeUt5RnVkQXRMUzFXUlU0SzRBV3EwQkhRZGxUUlV5UzZrOEt0Q3AzMU52RGMzSnpkNVpQS3hIZ3FhSFFCek9Kb2E0K05BYnNlNk1UbVRZeUxFVVlYd01WaWtjWFIxcGlob1NIdllvUTJ1aERqaVNpeGpnTW5IcWtiZlMxYytPbDM0bHVNUDdvT0xCTmxBT3ZSK3JMbithTnU2aDZ5SWVlNWNQbUM3enRkcjJJTllGOVBkR0Z3Y0pCMmNBYllnZFRXTVF1NEdQc1ZpVkNVQWV5dVVKL1lYbXB2Yno4cFdIV2ZmZmFaUEh6NDBQZGRUT2wzU2dhT2lTOExQMzc4K0RUVjZOVjMvLzc5WE1ERjJLTzlHRjNNSnpNa1h2L1hwdTJ0dFdxMHJSdTFncFljS05aeGRmRlljTGVxTFZhZjlXRlNBQy9FZkRIMmFBTzRwYVZseUZPa1E0ZVRlbVFWZUs1QVVGci9XRDMxTzl2WnIxMjd0bGM4a3gyME9qcForVHNkWTdWdCtvWmdEbFp1MHc0WWQrL2U3UWk0M25LcTRaM0E2clA1V2lJVmJRRGJlTER2b3RIcVJPVk8yd2krWUxJT25ibTV1Yjd1N3U3Y25qMTdTamV0TFhUcVVNdWZ4UCszbjVud2YrUEdEY3RpUmM5TEQrazJleGUzYWJPM3hzYkdPdHZhMmo0U3o0bjIram1uR3VLWm5KeTB5Nlc4NWZuYkk5citMVXFrTmtyY3JCcGRXTzVKQzVUWjJWbDdma1FhSzJSR1VhL3V2S2QxQnk4MUJUU1FUb3QveFlvcDE0SDNGRHRJMlN3bXozUktlN0pQdDluanRwblRiYjRWc00xVU02UU9IRGdnMDlQVGgzUjdoWVJpTm53MEpCR0wrb1IrVjQxT3pIYWExVTZ2Y052eUdjM056Y01TZG5KNlhvUHV2TjBrYkxtWjRhVitxYlVSTzFoZHJuS2I3NFpzMHpKa21obFNydlBxdllDaTBWYWZUZFFCN0taVmpuaUs5V2piTWk4TlpQK1hCc2dGcVRQTm5QM0xQYWNITSt0b0dwVDZTcFVoN1lDcFdUdXZEOS95RkIyS3VmcHNvbDlTUjNkcTcwNnJXYmkzMFZsWWUzY0g5RzVTNmtRRGFTQnBaKy9wNlpGTm16WU4xdm55cWxkMG01OUtsZlFBWnVmKzlubUtXYnU5N2dlNXRTYjZBTFkyWVVCblZvL3I0VzBZQ3piZFFmOG9kYUR2Ny9yTXpFeC9VaGszNWoycFpjOUl5bUdmeW0zcS8vL1BVcVVxc3U5b3JKTTN5ckdvblpSMnRuNWZHYTNTZnR6b0htbmRRWWYwenY2M1dnTHFpcjYvNHlIdFVHc0xhK1lmMGNBN1crTTJmOUlNK2thYTZ1MTMzMzBYbW4wSEJBU3djVm40ZWxJWjF5TjlUQnBzZkh5OHp3TEs5Lzh0d2RxZkE5b1pkTHlhUUxwOSs3WnMzNzU5U04vdnEyNmIxUVN5bGYxVy85L2YzYng1cytxZVp6dEE2Z0drSUo3c2E5WDhORlh6OVlnQS9vMjN1cXFaNGVQVkdCZTJUR3haMUhVSUpjMXNLajJuTG1rUUhaK1ltSGcvVFEvdzFhdFh4UUx3NGNPSEw1WWRQSmJiYnZrMmUvU0E4N3UwSFVzYXZKWjlQL1lVczIzMUNVb3l2eHBoSTNWMWRWM3luZldpK2pzNk92cHNKMCt5YTlldUh2MWJTWmxrekFKTXFtU3JoZWo0YTBFZkh0Vy92MjN4OTdwVFQ5dUVDYTBsRE5mN3hIWTNUbHpRN1IyMG16d1pQcHB5MngzVm9QczZUY1l0NTViMTZkV0h2VW5sN0dDaW45dUxnaElDdUl3R1hVRjN4a3UrY3ZQejg2K2VPM2Z1Q2ljNzFJZE4ydmpwcDU5ZTFNLysvejFGcmUxN2pNNnIzMUNGTHVQYXd0NEFibTV1dmpnd01NRDV3blZnQjBITnFEWU45SDhEaWc4UnZFOXJFanhseTVZdGw3VXFaOU1TMnhLS2RXemN1TEZWTS9iL2FPWVFwR2RqdmsxTlRmK3VELy9KVTNSU3MrKy8zYjE3TjdwMXI1SVF3QlZzQjlFZ2ZxQkJuTGhENmZPdnpjM04vYnhqeDQ1dklseExyUzZzM2FzZFZ6WU50TmRUMURySy9rTXo5UmVDcDlBR1hvYnVYRjlwa0hxSGpheTNkLy8rL1pmczZua0lwNSt2OWVvZjFpYkx0NzZ5V3ViUEdyd3ZDWjVCRzNnWnV0UFlaQWJ2VkViZENTL2FMSzFHVDdWY3k2eFhXei9mdlhxN0dGRGNab2Y5bzJCSkJQQXliQ3hUZDV3UEE0cDJhQkIvUlJDSGNVTlNlKzB6RTgvaUEvSmt6TGMvOWhNV2t0QUdUcUR0NFcrMmJ0MXF2YzJ2ZVlyYXFoUW5idCsrL2VXUkkwZW1iRW9pbnBVaWVBZTE2dXlkNWhvek1yQ0hEaG5aRGhTeVpHbWVUTHk4S29NMzZBUU0wSWtWUkllTDdDUjI2OVFLT1NQSnppSTZ2bjM3OXV1KzJWcXhLSnVvWVdPOWVWOTVDMTZiT2tyVjJZOEFEdFRkM1czTHUxajJDTGtBZUhGK2Z2Nk5iZHUyWGZuKysrOGxaaSs5OUZMdTNyMTdOc1BOT3F4Q0pyL1llTytyQkc4WXF0Q0JiSzZ2NjVrT09Uc25yMVh2YjNYSGZkOFdnSXR4eXFXOVo2MDI1KzdmdjMvZVZadERndGZhdldjSTNuQms0Q3JaU1FydWpKbWd6ODVPNmRPMjM0Y0hEeDZjakdXczJOcTcwOVBUbmUzdDdiMzYvczhIdm14Qm15aG45RUFaL1NvYjFTQ0FVNmcyaU1XMWkxdGJXNit2OXg1cXF6TGJwVURkR0c4KzhHVUViMG9NSTZXZ3cwdWptemR2L3NHR2ppUXNpRzJZNmJ5ZDc2ckRVcGVmZi81NVdXL1RMeTNyUHZmY2N6bGJPMXAvL0U4SnF6SWJncmNHQkhCS0ZzUmJ0bXdaMDUzdjk1Sjg0a081Z3Q1T2F3ZlgvK25PL29PT0djdGF6OGh1eUN6blZpdXh0bTQxRjRXeldWYi9PajQrL3QrQ1ZLaEMxNmpLSWFaeVExcXQvbEJmVjl5M2I5L0NXbXNmVytEYW1VUzJBSjJ0Rnhhd0VNSlRiS2pJMXMycWRTR0EyQkhBZFZCREVKdGZBN216czNNaDYyUEhia21oVXVDNnhlZDhxMGRXc2w3OFVYM1BiOURiWER1cTBIVmdweUR1MkxIamd1NlVyZnJqUDBoMUI4WkQ3a29ITC96eXl5OS8yYmx6NTE5Mzc5NHQ1ODZkazZ4a1pSc1N1bkhqaG1qN3ZWUlYxdmZaWjFsWG56cFkzVjk2TWoxeVptYm03SjA3ZDM0VTFJd01YR2RkWFYzdjZjNzlnWVJOK0hpR3JWR3RtZTJDanA5K3NYMzc5aW03WXNLcFU2ZWswV1BKdGoyN09xQWVWSEoyQlVMdFFiZEZEazVVVzFVdVkrM2Qvb21KaVhwZi9TRnFCUEFLcUxGS1hXNVkvOFlYRnN6YnRtMnpidXZTSkJLN2lrSzlBOXIrM3REUVVPbXg5WlpiME9yQjQ2UXR6T2N1SDVwMkNTSEx1clpVMFZtcXpQVkhBSzhnclFyMzZaMWRCQ3hWTmk1blFXQVhJTlBxNitVSER4NVlEL2pVcGsyYlJCK0xac2VGaHc4ZlNudDd1MmdWdk5UQlZCbmc5ck5WeVcvZHVtV1g3UlJ0Yjh2UFAvK2MweUMxQzRuWmlRWVdzSWMwK3gvVmJSMnRJZE9XSSt1dU1BSjRoYmxzM092VzJhcm41MjI5dDNiTzhnL3Vma3d6NTFSVFU5T1VCdldVeTlpL21wNmV0Z0R0MERJZHpjM05lVDBRZExobFlsL1ExMW83UEMvMVE5WnRFQUs0UVd6SldqZmNrcGYxKzdrdkJtNC9xMGMyQmdIY1lHN0I5dy9XV1NCYjRCYmRpUWdFYmdNUndLdkV6YWUyOXJGMUVLM1Y3NEdNdThvSTRGWG1xdGFuWFJ2WlpQMDdzWjV3YTE4UDIvVjVDZHpWUlFCbmhIVjI2VjNCRGRzVTNLK3o4djJVaHEvY2RaU0haMlptUHAxaU1leE1JSUF6YURHWXRZcjlCM2x5QXNUaUdHeWp2cS9GUlF1bTdPSmxRdEJtRmdHOEJyaHF0ZzM1SEpXbkE5clUraDMrdXNLSWRVVHBOa1pkdTNhTTZuSDJFY0Jya0YxaXRMVzE5WkFMYXBzazh2Y2FjRGF1MitIdTgwdTh6TExwWWdhMVFDMHVYcExVZnA2ZG5SMGx3d0lBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUZUbmIzZnF3ZGMrSVFIREFBQUFBRWxGVGtTdVFtQ0MiLz4KPC9kZWZzPgo8L3N2Zz4K);background-repeat:no-repeat;background-position:50% 50%;background-size:100%;display:block;height:56px;margin:10px auto 0;width:56px}.wdpun-notice .wdpun-notice-message{color:#23282d;display:block;font-family:"Open Sans",Arial,sans-serif;font-size:13px;line-height:20px;padding:10px}.wdpun-notice .wdpun-notice-message strong{font-weight:600}.wdpun-notice .wdpun-button{background:#17a8e3;border:2px solid #fff0;border-radius:4px;color:#fff;cursor:pointer;display:block;font-weight:500;font-size:16px;height:auto;line-height:18px;margin:0;padding:10px 20px;text-decoration:none;-webkit-transition:color 0.3s,opacity 0.3s,background 0.3s;transition:color 0.3s,opacity 0.3s,background 0.3s;white-space:nowrap}.wdpun-notice .wdpun-button:hover:not(:focus):not(:active){background-color:#1286b5;color:#fff}.wdpun-notice .wdpun-button:focus,.wdpun-notice .wdpun-button:active{background:#1286b5;outline:0;box-shadow:0 0 0 2px #e1f6ff}.wdpun-notice .wdpun-button-small{padding:5px 15px}.wdpun-notice .wdpun-button-notice-dismiss{background:#fff0;border:none;border-radius:0;color:#c5c5c5;padding:0;text-transform:none;-webkit-transition:color 0.3s;transition:color 0.3s}.wdpun-notice .wdpun-button-notice-dismiss:hover:not(:focus):not(:active),.wdpun-notice .wdpun-button-notice-dismiss:active,.wdpun-notice .wdpun-button-notice-dismiss:focus{background:#fff0;color:#666}.wdpun-notice .wdpun-notice-cta{border-top:1px solid #e5e5e5;background:#f8f8f8;clear:both;display:block;padding:15px 20px;position:relative;text-align:center;white-space:nowrap}.wdpun-notice .wdpun-notice-cta .wdpun-button{vertical-align:middle}.wdpun-notice .wdpun-notice-cta .wdpun-button-notice-dismiss{margin:10px auto 0}.wdpun-notice .wdpun-notice-cta input[type="email"]{line-height:20px;margin:0;max-width:320px;min-width:50px;padding-right:0;padding-left:0;text-align:center;vertical-align:middle}@media only screen and (min-width:601px){.wdpun-notice.notice{text-align:left}.wdpun-notice-logo{float:left;margin:10px}.wdpun-notice .wdpun-notice-message{margin-top:5px;margin-left:76px;padding:10px 20px 10px 10px}.wdpun-notice .wdpun-button{display:inline-block;font-size:14px}}@media only screen and (min-width:783px){.wdpun-notice .wdpun-notice-cta .wdpun-button-notice-dismiss{margin-top:0}.wdpun-notice button+button,.wdpun-notice .wdpun-button+button,.wdpun-notice button+.wdpun-button,.wdpun-notice .wdpun-button+.wdpun-button,.wdpun-notice a+button,.wdpun-notice a+.wdpun-button{margin-left:10px}}@media only screen and (min-width:961px){.wdpun-notice.notice{display:table}.wdpun-notice-logo{border-radius:0;height:auto;margin:0;min-height:80px;min-width:80px;width:5%}.wdpun-notice .wdpun-notice-logo,.wdpun-notice .wdpun-notice-message,.wdpun-notice .wdpun-notice-cta{cursor:default;display:table-cell;float:none;vertical-align:middle}.wdpun-notice .wdpun-notice-message{margin-top:0;max-width:100%;min-height:80px;width:75%}.wdpun-notice .wdpun-notice-cta{border-left:1px solid #e5e5e5;border-top:none;padding:0 30px;width:20%}}@media only screen and (min-width:1140px){.wdpun-notice .wdpun-button{font-size:13px}}
			</style>
			<?php
		}

		/**
		 * Notice scripts.
		 *
		 * @return void
		 */
		public function notice_scripts() {
			if ( ! $this->is_allowed_screen() ) {
				return;
			}
			?>
			<script type="text/javascript">
				!function($){function n(){function n(){a.fadeIn(500)}function i(){a.fadeTo(100,0,function(){a.slideUp(100,function(){a.remove()})})}function t(n,t){"0"!==e?(a.attr("data-message",t),a.addClass("saving"),s.action=n,jQuery.post(window.ajaxurl,s,i)):i()}var a=jQuery(".wdpun-notice"),e=a.find("input[name=msg_id]").val(),o=a.find(".wdpun-button-notice-dismiss"),s={};s.msg_id=e,o.on("click",function(n){n.preventDefault(),t("wdpun-dismiss",o.data("msg"))}),window.setTimeout(n,500)}$(n)}(jQuery);
			</script>
			<?php
		}

		/**
		 * Get plugin ID.
		 *
		 * @param string $plugin_file  Plugin file.
		 *
		 * @return string[]
		 */
		private function get_id_plugin( $plugin_file ) {
			return get_file_data(
				$plugin_file,
				array(
					'name'    => 'Plugin Name',
					'id'      => 'WDP ID',
					'version' => 'Version',
				)
			);
		}

		/**
		 * Simple check for updates.
		 *
		 * @return false|void
		 */
		public function updates_check() {
			global $wp_version;
			$local_projects = array();

			//----------------------------------------------------------------------------------//
			//plugins directory
			//----------------------------------------------------------------------------------//
			$plugins_root = WP_PLUGIN_DIR;
			if ( empty( $plugins_root ) ) {
				$plugins_root = ABSPATH . 'wp-content/plugins';
			}

			$plugins_dir  = @opendir( $plugins_root );
			$plugin_files = array();
			if ( $plugins_dir ) {
				while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
					if ( substr( $file, 0, 1 ) == '.' ) {
						continue;
					}
					if ( is_dir( $plugins_root . '/' . $file ) ) {
						$plugins_subdir = @opendir( $plugins_root . '/' . $file );
						if ( $plugins_subdir ) {
							while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
								if ( substr( $subfile, 0, 1 ) == '.' ) {
									continue;
								}
								if ( substr( $subfile, - 4 ) == '.php' ) {
									$plugin_files[] = "$file/$subfile";
								}
							}
						}
					} elseif ( substr( $file, - 4 ) == '.php' ) {
						$plugin_files[] = $file;
					}
				}
			}
			@closedir( $plugins_dir );
			@closedir( $plugins_subdir );

			if ( $plugins_dir && ! empty( $plugin_files ) ) {
				foreach ( $plugin_files as $plugin_file ) {
					if ( is_readable( "$plugins_root/$plugin_file" ) ) {

						unset( $data );
						$data = $this->get_id_plugin( "$plugins_root/$plugin_file" );

						if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
							$local_projects[ $data['id'] ]['type']     = 'plugin';
							$local_projects[ $data['id'] ]['version']  = $data['version'];
							$local_projects[ $data['id'] ]['filename'] = $plugin_file;
						}
					}
				}
			}

			//----------------------------------------------------------------------------------//
			// mu-plugins directory
			//----------------------------------------------------------------------------------//
			$mu_plugins_root = WPMU_PLUGIN_DIR;
			if ( empty( $mu_plugins_root ) ) {
				$mu_plugins_root = ABSPATH . 'wp-content/mu-plugins';
			}

			if ( is_dir( $mu_plugins_root ) && $mu_plugins_dir = @opendir( $mu_plugins_root ) ) {
				while ( ( $file = readdir( $mu_plugins_dir ) ) !== false ) {
					if ( substr( $file, - 4 ) == '.php' ) {
						if ( is_readable( "$mu_plugins_root/$file" ) ) {

							unset( $data );
							$data = $this->get_id_plugin( "$mu_plugins_root/$file" );

							if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
								$local_projects[ $data['id'] ]['type']     = 'mu-plugin';
								$local_projects[ $data['id'] ]['version']  = $data['version'];
								$local_projects[ $data['id'] ]['filename'] = $file;
							}
						}
					}
				}
				@closedir( $mu_plugins_dir );
			}

			//----------------------------------------------------------------------------------//
			// wp-content directory
			//----------------------------------------------------------------------------------//
			$content_plugins_root = WP_CONTENT_DIR;
			if ( empty( $content_plugins_root ) ) {
				$content_plugins_root = ABSPATH . 'wp-content';
			}

			$content_plugins_dir  = @opendir( $content_plugins_root );
			$content_plugin_files = array();
			if ( $content_plugins_dir ) {
				while ( ( $file = readdir( $content_plugins_dir ) ) !== false ) {
					if ( substr( $file, 0, 1 ) == '.' ) {
						continue;
					}
					if ( ! is_dir( $content_plugins_root . '/' . $file ) ) {
						if ( substr( $file, - 4 ) == '.php' ) {
							$content_plugin_files[] = $file;
						}
					}
				}
			}
			@closedir( $content_plugins_dir );

			if ( $content_plugins_dir && ! empty( $content_plugin_files ) ) {
				foreach ( $content_plugin_files as $content_plugin_file ) {
					if ( is_readable( "$content_plugins_root/$content_plugin_file" ) ) {
						unset( $data );
						$data = $this->get_id_plugin( "$content_plugins_root/$content_plugin_file" );

						if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
							$local_projects[ $data['id'] ]['type']     = 'drop-in';
							$local_projects[ $data['id'] ]['version']  = $data['version'];
							$local_projects[ $data['id'] ]['filename'] = $content_plugin_file;
						}
					}
				}
			}

			//----------------------------------------------------------------------------------//
			//themes directory
			//----------------------------------------------------------------------------------//
			$themes_root = WP_CONTENT_DIR . '/themes';
			if ( empty( $themes_root ) ) {
				$themes_root = ABSPATH . 'wp-content/themes';
			}

			$themes_dir   = @opendir( $themes_root );
			$themes_files = array();
			$local_themes = array();
			if ( $themes_dir ) {
				while ( ( $file = readdir( $themes_dir ) ) !== false ) {
					if ( substr( $file, 0, 1 ) == '.' ) {
						continue;
					}
					if ( is_dir( $themes_root . '/' . $file ) ) {
						$themes_subdir = @ opendir( $themes_root . '/' . $file );
						if ( $themes_subdir ) {
							while ( ( $subfile = readdir( $themes_subdir ) ) !== false ) {
								if ( substr( $subfile, 0, 1 ) == '.' ) {
									continue;
								}
								if ( substr( $subfile, - 4 ) == '.css' ) {
									$themes_files[] = "$file/$subfile";
								}
							}
						}
					} else {
						if ( substr( $file, - 4 ) == '.css' ) {
							$themes_files[] = $file;
						}
					}
				}
			}
			@closedir( $themes_dir );
			@closedir( $themes_subdir );

			if ( $themes_dir && ! empty( $themes_files ) ) {
				foreach ( $themes_files as $themes_file ) {
					// Skip child themes.
					if ( strpos( $themes_file, '-child' ) !== false ) {
						continue;
					}

					if ( is_readable( "$themes_root/$themes_file" ) ) {

						unset( $data );
						$data = $this->get_id_plugin( "$themes_root/$themes_file" );

						if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
							$local_projects[ $data['id'] ]['type']     = 'theme';
							$local_projects[ $data['id'] ]['filename'] = substr( $themes_file, 0, strpos( $themes_file, '/' ) );

							// Keep record of all themes for 133 themepack.
							if ( $data['id'] == $this->theme_pack ) {
								$local_themes[ $themes_file ]['id']       = $data['id'];
								$local_themes[ $themes_file ]['filename'] = substr( $themes_file, 0, strpos( $themes_file, '/' ) );
								$local_themes[ $themes_file ]['version']  = $data['version'];
								// Increment 133 theme pack version to lowest in all of them.
								if ( isset( $local_projects[ $data['id'] ]['version'] ) && version_compare( $data['version'], $local_projects[ $data['id'] ]['version'], '<' ) ) {
									$local_projects[ $data['id'] ]['version'] = $data['version'];
								} elseif ( ! isset( $local_projects[ $data['id'] ]['version'] ) ) {
									$local_projects[ $data['id'] ]['version'] = $data['version'];
								}
							} else {
								$local_projects[ $data['id'] ]['version'] = $data['version'];
							}
						}
					}
				}
			}
			update_site_option( 'wdp_un_local_themes', $local_themes );
			update_site_option( 'wdp_un_local_projects', $local_projects );

			// Now check the API.
			$projects   = array();
			$theme      = wp_get_theme();
			$ms_allowed = $theme->get_allowed();
			foreach ( $local_projects as $pid => $item ) {
				if ( ! empty( $blog_projects[ $pid ] ) ) { // Not yet implemented.
					// This project is activated on a blog!
					$active = true;
				} else {
					if ( is_multisite() ) {
						if ( 'theme' === $item['type'] ) {
							// If the theme is available on main site it's "active".
							$slug   = $item['filename'];
							$active = ! empty( $ms_allowed[ $slug ] );
						} else {
							if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
								require_once ABSPATH . 'wp-admin/includes/plugin.php';
							}
							$active = is_plugin_active_for_network( $item['filename'] );
						}
					} else {
						if ( 'theme' === $item['type'] ) {
							$slug   = $item['filename'];
							$active = $theme->stylesheet == $slug || $theme->template == $slug;
						} else {
							if ( ! function_exists( 'is_plugin_active' ) ) {
								require_once ABSPATH . 'wp-admin/includes/plugin.php';
							}
							$active = is_plugin_active( $item['filename'] );
						}
					}
				}
				$extra = '';

				/**
				 * Collect extra data from individual plugins.
				 *
				 * @since  4.0.0
				 * @api    wpmudev_api_project_extra_data-$pid
				 *
				 * @param  string $extra Default extra data is an empty string.
				 */
				$extra = apply_filters( "wpmudev_api_project_extra_data-$pid", $extra );
				$extra = apply_filters( 'wpmudev_api_project_extra_data', $extra, $pid );

				$projects[ $pid ] = array(
					'version' => $item['version'],
					'active'  => (bool) $active,
					'extra'   => $extra,
				);
			}

			// Get WP/BP version string to help with support.
			$wp_ver = is_multisite() ? "WordPress Multisite $wp_version" : "WordPress $wp_version";
			if ( defined( 'BP_VERSION' ) ) {
				$wp_ver .= ', BuddyPress ' . BP_VERSION;
			}

			// Add blog count if multisite.
			$blog_count = is_multisite() ? get_blog_count() : 1;

			$url = $this->server_url . 'updates';

			$options = array(
				'timeout'    => 15,
				'sslverify'  => false, // Many hosts have no updated CA bundle.
				'user-agent' => 'Dashboard Notification/' . $this->version,
			);

			$options['body'] = array(
				'blog_count' => $blog_count,
				'wp_version' => $wp_ver,
				'projects'   => wp_json_encode( $projects ),
				'domain'     => network_site_url(),
				'admin_url'  => network_admin_url(),
				'home_url'   => network_home_url(),
			);

			$response = wp_remote_post( $url, $options );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data = $response['body'];
				if ( 'error' !== $data ) {
					$data = json_decode( $data, true );
					if ( is_array( $data ) ) {
						// We've made it here with no errors, now check for available updates.
						$remote_projects = isset( $data['projects'] ) ? $data['projects'] : array();
						$updates         = array();

						// Check for updates.
						if ( is_array( $remote_projects ) ) {
							foreach ( $remote_projects as $id => $remote_project ) {
								if ( isset( $local_projects[ $id ] ) && is_array( $local_projects[ $id ] ) ) {
									// Match.
									$local_version  = $local_projects[ $id ]['version'];
									$remote_version = $remote_project['version'];

									if ( version_compare( $remote_version, $local_version, '>' ) ) {
										// Add to array.
										$updates[ $id ]                = $local_projects[ $id ];
										$updates[ $id ]['url']         = $remote_project['url'];
										$updates[ $id ]['name']        = $remote_project['name'];
										$updates[ $id ]['version']     = $local_version;
										$updates[ $id ]['new_version'] = $remote_version;
										$updates[ $id ]['autoupdate']  = $remote_project['autoupdate'];
									}
								}
							}

							// Record results.
							update_site_option( 'wdp_un_updates_available', $updates );
						} else {
							return false;
						}
					}
				}
			}
		}

		/**
		 * Filter plugin info.
		 *
		 * @param false|object|array $res     The result object or array. Default false.
		 * @param string             $action  The type of information being requested from the Installation API.
		 * @param object             $args    API arguments.
		 *
		 * @return false|object|array
		 */
		public function filter_plugin_info( $res, $action, $args ) {
			global $wp_version;
			$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

			if ( ( 'plugin_information' === $action || 'theme_information' === $action ) && strpos( $args->slug, 'wpmudev_install' ) !== false ) {
				$string  = explode( '-', $args->slug );
				$id      = intval( $string[1] );
				$updates = get_site_option( 'wdp_un_updates_available' );
				// If in details iframe on update core page short-circuit it.
				if ( did_action( 'install_plugins_pre_plugin-information' ) && is_array( $updates ) && isset( $updates[ $id ] ) ) {
					$this->popup_changelog( $id );
				}

				$res                = new stdClass();
				$res->name          = $updates[ $id ]['name'];
				$res->slug          = sanitize_title( $updates[ $id ]['name'] );
				$res->version       = $updates[ $id ]['version'];
				$res->rating        = 100;
				$res->homepage      = $updates[ $id ]['url'];
				$res->download_link = '';
				$res->tested        = $cur_wp_version;

				return $res;
			}

			if ( 'plugin_information' === $action && strpos( $args->slug, 'install_wpmudev_dash' ) !== false ) {
				$res                = new stdClass();
				$res->name          = 'WPMU DEV Dashboard';
				$res->slug          = 'wpmu-dev-dashboard';
				$res->version       = '';
				$res->rating        = 100;
				$res->homepage      = 'https://wpmudev.com/project/wpmu-dev-dashboard/';
				$res->download_link = $this->server_url . 'download-dashboard';
				$res->tested        = $cur_wp_version;

				return $res;
			}

			return $res;
		}

		/**
		 * Filter plugin rows.
		 *
		 * @return void
		 */
		public function filter_plugin_rows() {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			// Don't show on per site plugins list, just like core.
			if ( is_multisite() && ! is_network_admin() ) {
				return;
			}

			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( is_array( $updates ) && count( $updates ) ) {
				foreach ( $updates as $id => $plugin ) {
					if ( '2' !== $plugin['autoupdate'] ) {
						if ( 'theme' === $plugin['type'] ) {
							remove_all_actions( 'after_theme_row_' . $plugin['filename'] );
							add_action( 'after_theme_row_' . $plugin['filename'], array( $this, 'plugin_row' ), 99, 2 );
						} else {
							remove_all_actions( 'after_plugin_row_' . $plugin['filename'] );
							add_action( 'after_plugin_row_' . $plugin['filename'], array( $this, 'plugin_row' ), 99, 2 );
						}
					}
				}
			}

			$local_themes = get_site_option( 'wdp_un_local_themes' );
			if ( is_array( $local_themes ) && count( $local_themes ) ) {
				foreach ( $local_themes as $id => $plugin ) {
					remove_all_actions( 'after_theme_row_' . $plugin['filename'] );
					// Only add the notice if specific version is wrong.
					if ( isset( $updates[ $this->theme_pack ] ) && version_compare( $plugin['version'], $updates[ $this->theme_pack ]['new_version'], '<' ) ) {
						add_action( 'after_theme_row_' . $plugin['filename'], array( $this, 'themepack_row' ), 9, 2 );
					}
				}
			}
		}

		/**
		 * Filter plugin count.
		 *
		 * @param object $value  Plugin object.
		 *
		 * @return object
		 */
		public function filter_plugin_count( $value ) {
			global $wp_version;
			$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

			// Remove any conflicting slug local WPMU DEV plugins from WP update notifications.
			$local_projects = get_site_option( 'wdp_un_local_projects' );
			if ( is_array( $local_projects ) && count( $local_projects ) ) {
				foreach ( $local_projects as $id => $plugin ) {
					if ( isset( $value->response[ $plugin['filename'] ] ) ) {
						unset( $value->response[ $plugin['filename'] ] );
					}
				}
			}

			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( is_array( $updates ) && count( $updates ) && isset( $value->response ) ) {
				foreach ( $updates as $id => $plugin ) {
					if ( 'theme' !== $plugin['type'] && '2' !== $plugin['autoupdate'] ) {
						// Build plugin class.
						$object              = new stdClass();
						$object->url         = $plugin['url'];
						$object->slug        = "wpmudev_install-$id";
						$object->new_version = $plugin['new_version'];
						$object->package     = '';
						$object->tested      = $cur_wp_version;

						// Add to class.
						$value->response[ $plugin['filename'] ] = $object;
					}
				}
			}

			return $value;
		}

		/**
		 * Filter theme count.
		 *
		 * @param object $value  Theme object.
		 *
		 * @return object
		 */
		public function filter_theme_count( $value ) {
			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( is_array( $updates ) && count( $updates ) && isset( $value->response ) ) {
				foreach ( $updates as $id => $theme ) {
					if ( 'theme' === $theme['type'] && '2' !== $theme['autoupdate'] ) {
						$theme_slug = $theme['filename'];

						// Build theme listing.
						$value->response[ $theme_slug ]['theme']       = $theme['filename'];
						$value->response[ $theme_slug ]['url']         = admin_url( 'admin-ajax.php?action=wdpun-changelog&pid=' . $id );
						$value->response[ $theme_slug ]['new_version'] = $theme['new_version'];
						$value->response[ $theme_slug ]['package']     = '';
					}
				}
			}

			// Filter 133 theme pack themes from the list unless update is available.
			$local_themes = get_site_option( 'wdp_un_local_themes' );
			if ( is_array( $local_themes ) && count( $local_themes ) && isset( $value->response ) ) {
				foreach ( $local_themes as $id => $theme ) {
					$theme_slug = $theme['filename'];

					// Add to count only if new version exists, otherwise remove.
					if ( isset( $updates[ $theme['id'] ] ) && isset( $updates[ $theme['id'] ]['new_version'] ) && version_compare( $theme['version'], $updates[ $theme['id'] ]['new_version'], '<' ) ) {
						$value->response[ $theme_slug ]['new_version'] = $updates[ $theme['id'] ]['new_version'];
						$value->response[ $theme_slug ]['package']     = '';
					} elseif ( isset( $value ) && isset( $value->response ) && isset( $theme_slug ) && isset( $value->response[ $theme_slug ] ) ) {
						unset( $value->response[ $theme_slug ] );
					}
				}
			}

			return $value;
		}

		/**
		 * Plugin row.
		 *
		 * @param string $file         File.
		 * @param array  $plugin_data  Plugin data.
		 *
		 * @return false|void
		 */
		public function plugin_row( $file, $plugin_data ) {
			// Get new version and update url.
			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( is_array( $updates ) && count( $updates ) ) {
				foreach ( $updates as $id => $plugin ) {
					if ( $plugin['filename'] == $file ) {
						$project_id = $id;
						$version    = $plugin['new_version'];
						$plugin_url = $plugin['url'];
						$autoupdate = $plugin['autoupdate'];
						$filename   = $plugin['filename'];
						$type       = $plugin['type'];
						break;
					}
				}
			} else {
				return false;
			}

			$plugins_allowedtags = array(
				'a'       => array(
					'href'  => array(),
					'title' => array(),
				),
				'abbr'    => array( 'title' => array() ),
				'acronym' => array( 'title' => array() ),
				'code'    => array(),
				'em'      => array(),
				'strong'  => array(),
			);
			$plugin_name         = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

			$info_url = admin_url( 'admin-ajax.php?action=wdpun-changelog&pid=' . $project_id . '&TB_iframe=true&width=640&height=800' );
			if ( file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
				$message    = 'Activate WPMU DEV Dashboard';
				$action_url = $this->activate_url();
			} else { // Dashboard not installed at all.
				$message    = 'Install WPMU DEV Dashboard';
				$action_url = $this->auto_install_url();
			}

			if ( current_user_can( 'update_plugins' ) ) {
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';
				printf( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">%6$s</a> to update.', $plugin_name, esc_url( $info_url ), esc_attr( $plugin_name ), $version, esc_url( $action_url ), $message );
				echo '</p></div></td></tr>';
			}
		}

		/**
		 * Theme row.
		 *
		 * @param string $file         File.
		 * @param array  $plugin_data  Plugin data.
		 *
		 * @return false|void
		 */
		public function themepack_row( $file, $plugin_data ) {
			// Get new version and update url.
			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( isset( $updates[ $this->theme_pack ] ) ) {
				$plugin     = $updates[ $this->theme_pack ];
				$project_id = $this->theme_pack;
				$version    = $plugin['new_version'];
				$plugin_url = $plugin['url'];
			} else {
				return false;
			}

			$plugins_allowedtags = array(
				'a'       => array(
					'href'  => array(),
					'title' => array(),
				),
				'abbr'    => array( 'title' => array() ),
				'acronym' => array( 'title' => array() ),
				'code'    => array(),
				'em'      => array(),
				'strong'  => array(),
			);
			$plugin_name         = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

			$info_url = admin_url( 'admin_ajax.php?action=wdpun-changelog&pid=' . $project_id . '&TB_iframe=true&width=640&height=800' );
			if ( file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
				$message    = 'Activate WPMU DEV Dashboard';
				$action_url = $this->activate_url();
			} else { // Dashboard not installed at all.
				$message    = 'Install WPMU DEV Dashboard';
				$action_url = $this->auto_install_url();
			}

			if ( current_user_can( 'update_themes' ) ) {
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';
				printf( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">%6$s</a> to update.', $plugin_name, esc_url( $info_url ), esc_attr( $plugin_name ), $version, esc_url( $action_url ), $message );
				echo '</p></div></td></tr>';
			}
		}

		/**
		 * Disable checkboxes.
		 *
		 * @return void
		 */
		public function disable_checkboxes() {
			$updates = get_site_option( 'wdp_un_updates_available' );
			if ( ! is_array( $updates ) || ! count( $updates ) ) {
				return;
			}

			$jquery = "<script type='text/javascript'>";

			if ( file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
				$message    = 'Activate WPMU DEV Dashboard';
				$action_url = $this->activate_url();
			} else { // Dashboard not installed at all.
				$message    = 'Install WPMU DEV Dashboard';
				$action_url = $this->auto_install_url();
			}
			$jquery .= "var wdp_note = '<br><span class=\"notice inline notice-warning notice-alt\">" . sprintf( '<a href="%s">%s</a> to update.', esc_url( $action_url ), $message ) . "</span>';\n";

			foreach ( (array) $updates as $id => $project ) {
				$slug    = $project['filename'];
				$jquery .= "jQuery(\"input:checkbox[value='" . esc_attr( $slug ) . "']\").closest('tr').find('td p').last().append(wdp_note);\n";
				$jquery .= "jQuery(\"input:checkbox[value='" . esc_attr( $slug ) . "']\").remove();\n";
			}

			// Disable checkboxes for 133 theme pack themes.
			$local_themes = get_site_option( 'wdp_un_local_themes' );
			if ( is_array( $local_themes ) && count( $local_themes ) ) {
				foreach ( $local_themes as $id => $theme ) {
					$jquery .= "jQuery(\"input:checkbox[value='" . esc_attr( $theme['filename'] ) . "']\").closest('tr').find('td p').last().append(wdp_note);\n";
					$jquery .= "jQuery(\"input:checkbox[value='" . esc_attr( $theme['filename'] ) . "']\").remove();\n";
				}
			}

			$jquery .= "</script>\n";

			echo $jquery;
		}

		/**
		 * Set activate slug.
		 *
		 * @param string $plugin  Plugin.
		 *
		 * @return void
		 */
		public function set_activate_flag( $plugin ) {
			$data = $this->get_id_plugin( WP_PLUGIN_DIR . '/' . $plugin );
			if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
				update_site_option( 'wdp_un_activated_flag', 1 );
			}
		}

		/**
		 * Dashboard popup template: Project changelog
		 *
		 * Displays the changelog of a specific project.
		 *
		 * @since   4.0.5
		 *
		 * @param string $project_id  Project ID.
		 *
		 * @return void
		 */
		private function popup_changelog( $project_id ) {
			$url = $this->server_url . 'changelog/' . $project_id;

			$options = array(
				'timeout'    => 15,
				'sslverify'  => false, // Many hosts have no updated CA bundle.
				'user-agent' => 'Dashboard Notification/' . $this->version,
			);

			$response = wp_remote_get( $url, $options );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$changelog = json_decode( wp_remote_retrieve_body( $response ), true );
			}

			$updates = get_site_option( 'wdp_un_updates_available' );
			$item    = $updates[ $project_id ];

			if ( ! $changelog || ! is_array( $changelog ) || ! $item ) {
				wp_die( esc_html__( 'We did not find any data for this plugin or theme...', 'wpmudev' ) );
			}
			$dlg_id = 'dlg-' . md5( time() . '-' . $project_id );
			?>
			<div id="content" class="<?php echo esc_attr( $dlg_id ); ?>">
				<script src="<?php echo includes_url( '/wp-includes/js/jquery/jquery.js' ); ?>"></script>
				<link rel="stylesheet" href="https://fonts.bunny.net/css?family=Roboto+Condensed%3A400%2C700%7CRoboto%3A400%2C500%2C300%2C300italic%2C100" type="text/css" media="all"/>
				<style>
					* {
						box-sizing: border-box;
						-moz-box-sizing: border-box;
					}

					html, body {
						margin: 0;
						padding: 0;
						height: 100%;
						font-family: 'Roboto', 'Helvetica Neue', Helvetica, sans-serif;
						font-size: 15px;
					}

					h1, h2, h3, h4 {
						font-family: 'Roboto Condensed', 'Roboto', 'Helvetica Neue', Helvetica, sans-serif;
						font-weight: 700;
						color: #777771;
					}

					h1 {
						font-size: 3em;
					}

					p {
						font-size: 1.2em;
						font-weight: 300;
						color: #777771;
					}

					a {
						color: #19b4cf;
						text-decoration: none;
					}

					a:hover,
					a:focus,
					a:active {
						color: #387ac1;
					}

					#content {
						min-height: 100%;
						text-align: center;
						background: #FFF;
						position: absolute;
						left: 0;
						top: 0;
						right: 0;
						bottom: 0;
						overflow: auto;
					}

					#content .excerpt {
						width: 100%;
						background-color: #14485F;
						padding: 10px;
						color: #FFF;
					}

					#content .excerpt h1 {
						margin: 30px;
						color: #FFF;
						font-weight: 100;
					}

					#content .versions h4 {
						font-size: 15px;
						text-transform: uppercase;
						text-align: left;
						padding: 0 0 15px;
						font-weight: bold;
						line-height: 20px;
					}

					#content .excerpt a {
						float: left;
						margin-right: 40px;
						text-decoration: none;
						color: #6ECEDE;
					}

					#content .excerpt a:hover,
					#content .excerpt a:focus,
					#content .excerpt a:active {
						color: #C7F7FF;
					}

					#content .footer {
						background-color: #0B2F3F;
						padding: 20px 0;
						margin: 0;
						position: relative;
					}

					#content .footer p {
						color: #FFF;
						margin: 10px 0;
						padding: 0;
						font-size: 15px;
					}

					#content .information {
						padding: 0;
						text-align: left;
					}

					#content .versions > li {
						border-bottom: 1px solid #E5E5E5;
						padding: 40px;
						margin: 0;
					}

					#content .versions > li.new {
						background: #fffff6;
					}

					#content .information .current-version,
					#content .information .new-version {
						border-radius: 5px;
						color: #FFF;
						cursor: default;
						display: inline-block;
						position: relative;
						top: -2px;
						margin: 0 0 0 10px;
						padding: 1px 5px;
						font-size: 10px;
						line-height: 20px;
						height: 20px;
						box-sizing: border-box;
					}

					#content .information .new-version {
						background: #FDCE43;
						text-shadow: 0 1px 1px #DDAE30;
					}

					#content .current-version {
						background: #00ACCA;
						text-shadow: 0 1px 1px #008CAA;
					}

					#content .versions {
						margin: 0;
						padding: 0;
					}

					#content .versions .changes {
						list-style: disc;
						padding: 0 0 0 20px;
						margin: 0;
					}

					#content .versions .changes li {
						padding: 3px 0 3px 20px;
						margin: 0;
						color: #777771;
						cursor: default;
					}

					#content .version-meta {
						float: right;
						text-align: right;
					}
				</style>

				<div class="excerpt">
					<h1><?php printf( esc_attr__( '%s changelog', 'wpmudev' ), esc_html( $item['name'] ) ); ?></h1>
				</div>

				<div class="information">

					<ul class="versions">
						<?php
						foreach ( $changelog as $log ) {
							$row_class = '';
							$badge     = '';

							if ( ! is_array( $log ) ) {
								continue;
							}
							if ( empty( $log ) ) {
								continue;
							}

							// -1 .. local is higher (dev) | 0 .. equal | 1 .. new version available
							$version_check = version_compare( $log['version'], $item['version'] );

							if ( $item['version'] && 1 === $version_check ) {
								$row_class = 'new';
							}

							if ( $item['version'] ) {
								if ( 0 === $version_check ) {
									$badge = sprintf(
										'<div class="current-version">%s %s</div>',
										'<i class="wdv-icon wdv-icon-ok"></i>',
										__( 'Current', 'wpmudev' )
									);
								} elseif ( 1 === $version_check ) {
									$badge = sprintf(
										'<div class="new-version">%s %s</div>',
										'<i class="wdv-icon wdv-icon-star"></i>',
										__( 'New', 'wpmudev' )
									);
								}
							}

							$version = $log['version'];

							if ( empty( $log['time'] ) ) {
								$rel_date = '';
							} else {
								$rel_date = date_i18n( get_option( 'date_format' ), $log['time'] );
							}

							printf(
								'<li class="%1$s"><h4>%2$s %3$s <small class="version-meta">%4$s</small></h4>',
								esc_attr( $row_class ),
								sprintf( /* translators: %s - version */
									esc_html__( 'Version %s', 'wpmudev' ),
									esc_html( $version )
								),
								wp_kses_post( $badge ),
								esc_html( $rel_date )
							);

							$notes        = explode( "\n", $log['log'] );
							$detail_level = 0;
							$detail_class = 'intro';

							echo '<ul class="changes">';
							foreach ( $notes as $note ) {
								if ( 0 === strpos( $note, '<p>' ) ) {
									if ( 1 == $detail_level ) {
										printf(
											'<li class="toggle-details">
									<a href="#" class="for-intro">%s</a><a href="#" class="for-detail">%s</a>
									</li>',
											esc_html__( 'Show all changes', 'wpmudev' ),
											esc_html__( 'Hide details', 'wpmudev' )
										);
										$detail_class = 'detail';
									}
									$detail_level += 1;
								}

								$note = stripslashes( $note );
								$note = preg_replace( '/(<br ?\/?>|<p>|<\/p>)/', '', $note );
								$note = trim( preg_replace( '/^\s*(\*|\-)\s*/', '', $note ) );
								$note = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $note );
								$note = preg_replace( '/`(.*?)`/', '<code>\1</code>', $note );
								if ( empty( $note ) ) {
									continue;
								}

								printf(
									'<li class="version-%s">%s</li>',
									esc_attr( $detail_class ),
									wp_kses_post( $note )
								);
							}
							echo '</ul></li>';
						}
						?>
					</ul>
				</div>

				<div class="footer">
					<p>Copyright 2009 - <?php echo esc_html( date( 'Y' ) ); ?> WPMU DEV</p>
				</div>

				<style>
					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes .for-detail,
					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes .version-detail {
						display: none;
					}

					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes .for-intro {
						display: inline-block;
					}

					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes.show-details .for-intro {
						display: none;
					}

					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes.show-details .for-detail {
						display: inline-block;
					}

					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes.show-details .version-detail {
						display: list-item;
					}

					.<?php echo esc_attr( $dlg_id ); ?> .versions ul.changes .toggle-details {
						padding: 8px 0 4px;
						text-align: right;
						font-size: 12px;
						list-style: none;
					}
				</style>
				<script>
					jQuery(function () {
						jQuery('.<?php echo esc_attr( $dlg_id ); ?>').on('click', '.toggle-details a', function (ev) {
							var li = jQuery(this),
								ver = li.closest('.changes');

							ev.preventDefault();
							ev.stopPropagation();
							ver.toggleClass('show-details');
							return false;
						});
					});
				</script>
			</div>
			<?php
			exit; // This is for output, we are done after this.
		}

		/**
		 * Show changelog.
		 *
		 * @return void
		 */
		public function popup_changelog_ajax() {
			$project_id = filter_input( INPUT_GET, 'pid', FILTER_UNSAFE_RAW );
			$project_id = sanitize_key( $project_id );
			$this->popup_changelog( $project_id );
		}

		/**
		 * Dismiss ajax callback.
		 *
		 * @return void
		 */
		public function dismiss_ajax() {
			update_site_option( 'wdp_un_dismissed', time() );
			wp_send_json_success();
		}

	}

	$GLOBALS['WPMUDEV_Dashboard_Notice4'] = new WPMUDEV_Dashboard_Notice4();
}

// Disable older versions.
if ( ! class_exists( 'WPMUDEV_Dashboard_Notice' ) ) {
	class WPMUDEV_Dashboard_Notice {}
}

if ( ! class_exists( 'WPMUDEV_Dashboard_Notice3' ) ) {
	class WPMUDEV_Dashboard_Notice3 {}
}