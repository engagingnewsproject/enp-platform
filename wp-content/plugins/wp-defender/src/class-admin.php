<?php
/**
 * Handles WordPress admin page related tasks.
 *
 * @package WP_Defender
 */

namespace WP_Defender;

use WP_Defender\Component\Rate;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Firewall;
use WP_Defender\Integrations\Dashboard_Whitelabel;
use WP_Defender\Component\Config\Config_Hub_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Handles WordPress admin page related tasks.
 *
 * @since 2.4
 */
class Admin {

	/**
	 * Is the Free version?
	 *
	 * @var bool
	 */
	public $is_wp_org_version;

	/**
	 * Constructor for the Admin class.
	 */
	public function __construct() {
		$this->is_wp_org_version = defender_is_wp_org_version();
		add_action( 'wp_ajax_defender_ip_detection_notice_dismiss', array( $this, 'dismiss_notice' ) );
		add_action( 'wp_ajax_defender_ip_detection_switch_to_xff', array( $this, 'switch_to_xff' ) );
		add_action( 'admin_head', array( $this, 'add_global_styles' ) );
	}

	/**
	 * Add global styles.
	 */
	public function add_global_styles() {
		echo '<style>
			#toplevel_page_wp-defender ul.wp-submenu li a[href="admin.php?page=wdf-ip-lockout"] { display: flex; justify-content: space-between; align-items: center; }
		</style>';
	}

	/**
	 * Init admin actions.
	 */
	public function init() {
		// Display plugin links.
		add_filter( 'network_admin_plugin_action_links_' . DEFENDER_PLUGIN_BASENAME, array( $this, 'settings_link' ) );
		add_filter( 'plugin_action_links_' . DEFENDER_PLUGIN_BASENAME, array( $this, 'settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );
		// Only for plugin pages and actions are only for wp.org members.
		if ( $this->is_wp_org_version ) {
			add_action( 'admin_init', array( $this, 'register_free_modules' ), 20 );
			/**
			 * Action hook that fires after a scan issue is fixed.
			 *
			 * @since 4.4.0
			 */
			add_action(
				'wpdef_fixed_scan_issue',
				array( $this, 'after_scan_fix' ),
				10
			);
			// For submenu callout.
			add_action( 'admin_head', array( $this, 'retarget_submenu_callout' ) );
			if ( ! wd_di()->get( WPMUDEV::class )->is_wpmu_hosting() ) {
				add_submenu_page(
					'wp-defender',
					esc_html__( 'Limited-time offer!', 'wpdef' ),
					esc_html__( 'Limited-time offer!', 'wpdef' ),
					is_multisite() ? 'manage_network_options' : 'manage_options',
					$this->get_link( 'upsell', 'defender_submenu_upsell' )
				);
			}
		}

		// Display IP detection notice.
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * Retrieves the display name of the plugin.
	 *
	 * @return string The display name of the plugin with a trailing hyphen.
	 */
	public function get_plugin_display_name(): string {
		// Check if the plugin is the WordPress.org version (i.e., the free version) and set the label accordingly.
		$plugin_label = $this->is_wp_org_version
										? esc_html__( 'Defender', 'wpdef' )
										: esc_html__( 'Defender Pro', 'wpdef' );

		// Instantiate the Dashboard_Whitelabel class only if necessary.
		$whitelabel = new Dashboard_Whitelabel();

		// If whitelabeling is enabled and a custom name is provided, use it.
		if ( $whitelabel->can_whitelabel() ) {
			$custom_label = $whitelabel->get_plugin_name( Config_Hub_Helper::WDP_ID );
			if ( ! empty( $custom_label ) ) {
				$plugin_label = $custom_label;
			}
		}

		// Return the final plugin label with the appended dash.
		return $plugin_label . ' - ';
	}

	/**
	 * The method is a stub without content.
	 */
	private function menu_nope(): void {
	}

	/**
	 * Generates the submenu callout for the WP Defender plugin.
	 *
	 * @return void
	 */
	public function retarget_submenu_callout(): void {
		?>
		<style>
			#toplevel_page_wp-defender > ul > li:last-child > a[href^="https://wpmudev.com/"],
			#toplevel_page_wp-defender > ul > li:last-child > a[href^="https://wpmudev.com/"]:hover,
			#toplevel_page_wp-defender > ul > li:last-child > a[href^="https://wpmudev.com/"]:active,
			#toplevel_page_wp-defender > ul > li:last-child > a[href^="https://wpmudev.com/"]:focus {
				background: #8D00B1;
				color: #ffffff;
				font-weight: 500;
			}

			#toplevel_page_wp-defender.wp-not-current-submenu > ul > li:last-child > a[href^="https://wpmudev.com/"],
			#toplevel_page_wp-defender.wp-not-current-submenu > ul > li:last-child > a[href^="https://wpmudev.com/"]:hover,
			#toplevel_page_wp-defender.wp-not-current-submenu > ul > li:last-child > a[href^="https://wpmudev.com/"]:active,
			#toplevel_page_wp-defender.wp-not-current-submenu > ul > li:last-child > a[href^="https://wpmudev.com/"]:focus {
				margin-left: -4px;
			}
		</style>
		<script type='text/javascript'>
			jQuery(function ($) {
				$('#toplevel_page_wp-defender > ul > li:last-child > a[href^="https://wpmudev.com/"]').attr("target", "_blank");
			});
		</script>
		<?php
	}

	/**
	 * Fired when the scan issue is fixed.
	 *
	 * @return void
	 */
	public function after_scan_fix(): void {
		Rate::run_counter_of_fixed_scans();
	}

	/**
	 * Return URL link.
	 *
	 * @param  string $link_for  Accepts: 'docs', 'plugin', 'rate' and etc.
	 * @param  string $campaign  Utm campaign tag to be used in link. Default: ''.
	 * @param  string $adv_path  Advanced path. Default: ''.
	 *
	 * @return string
	 */
	public function get_link( $link_for, $campaign = '', $adv_path = '' ): string {
		$domain   = 'https://wpmudev.com';
		$wp_org   = 'https://wordpress.org';
		$utm_tags = "?utm_source=defender&utm_medium=plugin&utm_campaign={$campaign}";
		switch ( $link_for ) {
			case 'docs':
				$link = "{$domain}/docs/wpmu-dev-plugins/defender/{$utm_tags}";
				break;
			case 'plugin':
			case 'upsell':
				$link = "{$domain}/project/wp-defender/{$utm_tags}";
				break;
			case 'rate':
				$link = "{$wp_org}/support/plugin/defender-security/reviews/#new-post";
				break;
			case 'support':
				$link = $this->is_wp_org_version
					? "{$wp_org}/support/plugin/defender-security/"
					: "{$domain}/get-support/";
				break;
			case 'roadmap':
				$link = "{$domain}/roadmap/";
				break;
			case 'pro_link':
				$link = "{$domain}/$adv_path";
				break;
			default:
				$link = '';
				break;
		}

		return $link;
	}

	/**
	 * Adds a settings link on plugin page.
	 *
	 * @param  array $links  Current links.
	 *
	 * @return array
	 */
	public function settings_link( $links ) {
		$action_links = array();
		$wpmu_dev     = new WPMUDEV();
		// Dashboard-link.
		$action_links['dashboard'] = '<a href="' . network_admin_url( 'admin.php?page=wp-defender' ) . '" aria-label="' . esc_attr(
			esc_html__(
				'Go to Defender Dashboard',
				'wpdef'
			)
		) . '">' . esc_html__( 'Dashboard', 'wpdef' ) . '</a>';
		// Documentation-link.
		$action_links['docs'] = '<a target="_blank" href="' . $this->get_link(
			'docs',
			'defender_pluginlist_docs'
		) . '" aria-label="' . esc_attr(
			esc_html__(
				'Docs',
				'wpdef'
			)
		) . '">' . esc_html__( 'Docs', 'wpdef' ) . '</a>';
		if ( ! $wpmu_dev->is_member() ) {
			if ( WP_DEFENDER_PRO_PATH !== DEFENDER_PLUGIN_BASENAME ) {
				if ( ! wd_di()->get( WPMUDEV::class )->is_wpmu_hosting() ) {
					$action_links['upgrade'] = '<a style="color: #8D00B1;" target="_blank" href="' . $this->get_link(
						'plugin',
						'defender_pluginlist_upgrade'
					) . '" aria-label="' . esc_attr(
						esc_html__(
							'Upgrade to Defender Pro',
							'wpdef'
						)
					) . '">' . esc_html__( 'Limited-time offer!', 'wpdef' ) . '</a>';
				}
			} elseif ( ! $wpmu_dev->is_hosted_site_connected_to_tfh() ) {
				$action_links['renew'] = '<a style="color: #8D00B1;" target="_blank" href="' . $this->get_link(
					'plugin',
					'defender_pluginlist_renew'
				) . '" aria-label="' . esc_attr(
					esc_html__(
						'Renew Your Membership',
						'wpdef'
					)
				) . '">' . esc_html__( 'Renew Membership', 'wpdef' ) . '</a>';
			}
		}

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param  string[] $links  Plugin Row Meta.
	 * @param  string   $file  Plugin Base file.
	 * @param  array    $plugin_data  Plugin data.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file, $plugin_data ) {
		$row_meta = array();
		if ( ! defined( 'DEFENDER_PLUGIN_BASENAME' ) || DEFENDER_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		// Change AuthorURI link.
		if ( isset( $links[1] ) ) {
			$author_uri = $this->is_wp_org_version ? 'https://profiles.wordpress.org/wpmudev/' : 'https://wpmudev.com/';
			$author_uri = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				$author_uri,
				esc_html__( 'WPMU DEV', 'wpdef' )
			);
			$links[1]   = sprintf(
			/* translators: %s: Author URI. */
				esc_html__( 'By %s', 'wpdef' ),
				$author_uri
			);
		}

		if ( $this->is_wp_org_version ) {
			// Change AuthorURI link.
			if ( isset( $links[2] ) && false === strpos( $links[2], 'target="_blank"' ) ) {
				if ( ! isset( $plugin_data['slug'] ) && $plugin_data['Name'] ) {
					$links[2] = sprintf(
						'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
						esc_url(
							network_admin_url(
								'plugin-install.php?tab=plugin-information&plugin=defender-security&TB_iframe=true&width=600&height=550'
							)
						),
						/* translators: %s: Plugin name. */
						esc_attr( sprintf( esc_html__( 'More information about %s', 'wpdef' ), $plugin_data['Name'] ) ),
						esc_attr( $plugin_data['Name'] ),
						esc_html__( 'View details', 'wpdef' )
					);
				} else {
					$links[2] = str_replace( 'href=', 'target="_blank" href=', $links[2] );
				}
			}
			$row_meta['rate']    = '<a href="' . esc_url( $this->get_link( 'rate' ) ) . '" aria-label="' . esc_attr__(
				'Rate Defender',
				'wpdef'
			) . '" target="_blank">' . Rate::get_rate_button_title() . '</a>';
			$row_meta['support'] = '<a href="' . esc_url( $this->get_link( 'support' ) ) . '" aria-label="' . esc_attr__(
				'Support',
				'wpdef'
			) . '" target="_blank">' . esc_html__( 'Support', 'wpdef' ) . '</a>';
		} else {
			// Change 'Visit plugins' link to 'View details'.
			if ( isset( $links[2] ) && false !== strpos( $links[2], 'project/wp-defender' ) ) {
				$links[2] = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $this->get_link( 'pro_link', '', 'project/wp-defender/' ) ),
					esc_html__( 'View details', 'wpdef' )
				);
			}
			$row_meta['support'] = '<a href="' . esc_url( $this->get_link( 'support' ) ) . '" aria-label="' . esc_attr__(
				'Premium Support',
				'wpdef'
			) . '" target="_blank">' . esc_html__( 'Premium Support', 'wpdef' ) . '</a>';
		}
		$row_meta['roadmap'] = '<a href="' . esc_url( $this->get_link( 'roadmap' ) ) . '" aria-label="' . esc_attr__(
			'Roadmap',
			'wpdef'
		) . '" target="_blank">' . esc_html__( 'Roadmap', 'wpdef' ) . '</a>';

		return array_merge( $links, $row_meta );
	}

	/**
	 * Register sub-modules.
	 */
	public function register_free_modules() {
		$module_path = defender_path( 'extra/free-dashboard/module.php' );
		if ( ! file_exists( $module_path ) ) {
			return;
		}
		/* @noinspection PhpIncludeInspection */
		require_once $module_path;
		// Register the current plugin.
		do_action(
			'wdev_register_plugin',
			/* 1             Plugin ID */ DEFENDER_PLUGIN_BASENAME,
			/* 2          Plugin Title */ 'Defender',
			/* 3 https://wordpress.org */ '/plugins/defender-security/',
			/* 4      Email Button CTA */ esc_html__( 'Get Fast!', 'wpdef' )
		);
	}

	/**
	 * Display IP detection notices for if user site is behind proxy, e.g. Cloudflare or something else, and only for admins.
	 *
	 * @return void
	 */
	public function admin_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$header = $this->get_plugin_display_name();
		if ( Firewall::is_cf_notice_ready() ) {
			$is_show      = 'cf';
			$class_notice = 'notice-info';
			$header      .= esc_html__(
				'Cloudflare Usage Detected: Switched to CF-Connecting-IP for Better Compatibility',
				'wpdef'
			);
		} elseif ( Firewall::is_xff_notice_ready() ) {
			$is_show      = 'xff';
			$class_notice = 'notice-warning';
			$header      .= esc_html__(
				'Improve IP Detection: We suggest Switching to X-Forward-For IP Detection Method',
				'wpdef'
			);
		} else {
			return;
		}
		?>
		<div class="defender_ip_detection_notice notice <?php echo esc_attr( $class_notice ); ?> is-dismissible"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'defender_ip_detection_notice_dismiss' ) ); ?>"
			data-prop="notice-for-<?php echo esc_attr( $is_show ); ?>">
			<h3 style="margin-bottom:0;">
				<?php echo esc_html( $header ); ?>
			</h3>
			<?php if ( 'cf' === $is_show ) { ?>
				<p style="color: #72777C; line-height: 22px;">
					<?php
					printf(
							/* translators: %s: Link. */
						esc_html__(
							'We have switched to using the CF-Connecting-IP HTTP header for IP detection, offering enhanced compatibility for users behind Cloudflare Proxy. If you wish to change this setting, you can do so from %s.',
							'wpdef'
						),
						'<a style="font-weight:bold;" href="' . esc_url_raw( network_admin_url( 'admin.php?page=wdf-ip-lockout&view=settings#detect-ip-addresses' ) ) . '">' . esc_html__( 'here', 'wpdef' ) . '</a>'
					);
					?>
				</p>
				<p>
					<button type="button" class="button button-primary button-large defender_ip_detection_action_hide"
							data-prop="defender_ip_detection_notice_success">
						<?php
						esc_html_e(
							'Ok, I understand',
							'wpdef'
						);
						?>
					</button>
				</p>
			<?php } elseif ( 'xff' === $is_show ) { ?>
				<p style="color: #72777C; line-height: 22px;">
					<?php
					printf(
							/* translators: %s: Link. */
						esc_html__(
							'Based on your server configuration, we recommend switching to the X-Forwarded-For method for accurate IP detection and to prevent firewall blocks. Easily modify your settings %s.',
							'wpdef'
						),
						'<a style="font-weight:bold;" href="' . esc_url_raw( network_admin_url( 'admin.php?page=wdf-ip-lockout&view=settings#detect-ip-addresses' ) ) . '">' . esc_html__( 'here', 'wpdef' ) . '</a>'
					);
					?>
				</p>
				<p>
					<button type="button" class="button button-primary button-large"
							id="defender_ip_detection_action_switch"
							data-prop="defender_ip_detection_notice_success">
						<?php esc_html_e( 'Switch to X-Forwarded-For', 'wpdef' ); ?>
					</button>
					<a href="#" class="defender_ip_detection_action_hide"
						style="margin-left: 11px; line-height: 16px; text-decoration: none; font-weight: bold;"
						data-prop="defender_ip_detection_notice_dismiss"><?php esc_html_e( 'Dismiss', 'wpdef' ); ?></a>
				</p>
			<?php } ?>
		</div>
		<script type="text/javascript">
			//Switch.
			jQuery('#defender_ip_detection_action_switch').on('click', function (e) {
				e.preventDefault();
				var $notice = jQuery(e.currentTarget).closest('.defender_ip_detection_notice'),
					ajaxUrl = '<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'defender_ip_detection_switch_to_xff',
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
			//Hide.
			jQuery('body').on('click', '.defender_ip_detection_notice .notice-dismiss, .defender_ip_detection_action_hide', function (e) {
				e.preventDefault();
				var $notice = jQuery(e.currentTarget).closest('.defender_ip_detection_notice'),
					ajaxUrl = '<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'defender_ip_detection_notice_dismiss',
						prop: $notice.data('prop'),
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
		</script>
		<?php
	}

	/**
	 * Dismiss notice.
	 *
	 * @return void
	 */
	public function dismiss_notice(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! check_ajax_referer( 'defender_ip_detection_notice_dismiss' )
		) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid request, you are not allowed to do that action.', 'wpdef' ) )
			);
		}

		$prop        = defender_get_data_from_request( 'prop', 'p' );
		$notice_type = ! empty( $prop ) ? $prop : false;
		if ( 'notice-for-cf' === $notice_type ) {
			update_site_option( Firewall::IP_DETECTION_CF_DISMISS_SLUG, true );
			wp_send_json_success();
		} elseif ( 'notice-for-xff' === $notice_type ) {
			update_site_option( Firewall::IP_DETECTION_XFF_DISMISS_SLUG, true );
			wp_send_json_success();
		} else {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid request, allowed data not provided.', 'wpdef' ) )
			);
		}
	}

	/**
	 * Switch to XFF option.
	 *
	 * @return void
	 */
	public function switch_to_xff(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! check_ajax_referer( 'defender_ip_detection_notice_dismiss' )
		) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid request, you are not allowed to do that action.', 'wpdef' ) )
			);
		}
		// Change model's data.
		$model_firewall                 = wd_di()->get( Model\Setting\Firewall::class );
		$model_firewall->http_ip_header = 'HTTP_X_FORWARDED_FOR';
		$xff_ip                         = defender_get_data_from_request( 'HTTP_X_FORWARDED_FOR', 's' );
		if ( empty( $model_firewall->trusted_proxies_ip ) ) {
			$model_firewall->trusted_proxies_ip = $xff_ip;
		} else {
			// Todo: improve the code using a separate method. This will be useful when the user switches between different proxy headeres (IP detection options).
			$separator = "\r\n";
			// Check if the XFF header contains multiple IPs.
			$xff_ip                             = str_replace( array( ',', ' ,' ), $separator, $xff_ip );
			$model_firewall->trusted_proxies_ip = $model_firewall->trusted_proxies_ip . $separator . $xff_ip;
		}
		$model_firewall->save();
		// Save Dismiss slug.
		update_site_option( Firewall::IP_DETECTION_XFF_DISMISS_SLUG, true );
		wp_send_json_success();
	}
}