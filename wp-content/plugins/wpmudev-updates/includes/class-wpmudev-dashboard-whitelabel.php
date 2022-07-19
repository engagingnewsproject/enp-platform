<?php
/**
 * Class that handles whitelabel functionality.
 *
 * @link    https://wpmudev.com
 * @since   4.11.1
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Whitelabel
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Whitelabel
 */
class WPMUDEV_Dashboard_Whitelabel {

	/**
	 * WPMUDEV_Dashboard_Whitelabel constructor.
	 *
	 * @since 4.11.1
	 */
	public function __construct() {
		// Modify admin menus.
		add_action( 'admin_menu', array( $this, 'whitelabel_menu' ), 99 );
		add_action( 'network_admin_menu', array( $this, 'whitelabel_menu' ), 99 );

		// Remove deleted blogs.
		add_action( 'delete_blog', array( $this, 'remove_deleted_site' ) );

		// Filtering wpmudev branding being used.
		add_filter( 'wpmudev_branding', array( $this, 'get_branding' ), 10, 2 );
		add_filter( 'wpmudev_branding_hide_branding', array( $this, 'get_hide_branding' ) );
		add_filter( 'wpmudev_branding_hero_image', array( $this, 'get_branding_hero_image' ) );
		add_filter( 'wpmudev_branding_change_footer', array( $this, 'get_branding_change_footer' ) );
		add_filter( 'wpmudev_branding_footer_text', array( $this, 'get_branding_footer_text' ) );
		add_filter( 'wpmudev_branding_hide_doc_link', array( $this, 'get_branding_hide_doc_link' ) );
	}

	/**
	 * Print menu icon style inline.
	 *
	 * This is required because users may upload bigger sized
	 * icons even if we have recommended 20x20 size.
	 *
	 * @since 4.11.1
	 *
	 * @return void
	 */
	public function print_icon_style() {
		?>
		<style>
            #adminmenuwrap #adminmenu div.wp-menu-image img {
                width: 20px;
                height: 20px;
                object-fit: scale-down;
            }
		</style>
		<?php
	}

	/**
	 * White label admin menu for plugins.
	 *
	 * For WPMUDEV plugins, see if white label is configured
	 * in Dash. If so, change label and icon.
	 *
	 * @since 4.11.1
	 *
	 * @return void
	 */
	public function whitelabel_menu() {
		global $menu;

		// Get available configs.
		$configs = $this->get_menu_configs();
		// No need to continue if empty.
		if ( empty( $configs ) ) {
			return;
		}

		// Get plugin slugs.
		$enabled = array_keys( $configs );

		// On multisite, always do it on main site.
		if ( is_multisite() ) {
			// Doing this here to avoid multiple switches.
			switch_to_blog( $this->main_site_id() );
		}

		foreach ( $menu as $position => $data ) {
			// Only when it's required menu.
			if ( isset( $data[2] ) && in_array( $data[2], $enabled, true ) ) {
				// Change plugin menu title and icon if required.
				// phpcs:ignore
				$menu[ $position ] = $this->process_menu( $data );
			}
		}

		// Restore previous blog.
		if ( is_multisite() ) {
			restore_current_blog();
		}
	}

	/**
	 * On site delete, remove it from whitelabel list.
	 *
	 * Doing this to avoid unwanted errors if one site
	 * is dropped.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @since 4.11.1
	 */
	public function remove_deleted_site( $site_id ) {
		// Get whitelabel settings.
		$settings = $this->get_settings();
		// Get already added sites.
		$sites = empty( $settings['labels_subsites'] ) ? array() : (array) $settings['labels_subsites'];
		// Delete current item if already exist.
		if ( in_array( $site_id, $sites ) ) { // phpcs:ignore
			// Remove the site id.
			$key = array_search( $site_id, $sites ); // phpcs:ignore
			unset( $sites[ $key ] );
		}
		// Update new list.
		WPMUDEV_Dashboard::$settings->set( 'labels_subsites', $sites, 'whitelabel' );
	}

	/**
	 * Get WPMUDEV branding that should be used.
	 *
	 * Other plugins can use this data to enable whitelabel.
	 *
	 * @param array $branding Default data.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return array
	 */
	public function get_branding( $branding = array() ) {
		// Only if white label enabled.
		if ( $this->can_whitelabel() ) {
			// Only array is allowed.
			if ( ! is_array( $branding ) ) {
				$branding = array();
			}

			// Default values.
			$default = array(
				'hide_branding' => false,
				'hero_image'    => '',
				'change_footer' => false,
				'footer_text'   => sprintf(
				// translators: %s Heart icon.
					__( 'Made with %s by WPMU DEV', 'wpmudev' ),
					'<i class="sui-icon-heart" aria-hidden="true"></i>'
				),
				'hide_doc_link' => false,
			);

			// Merge data.
			$branding = wp_parse_args( $branding, $default );

			// Setup branding data.
			$branding['hide_branding'] = $this->get_hide_branding( $branding['hide_branding'] );
			$branding['hero_image']    = $this->get_branding_hero_image( $branding['hero_image'] );
			$branding['change_footer'] = $this->get_branding_change_footer( $branding['change_footer'] );
			$branding['footer_text']   = $this->get_branding_footer_text( $branding['footer_text'] );
			$branding['hide_doc_link'] = $this->get_branding_hide_doc_link( $branding['hide_doc_link'] );
		}

		return $branding;
	}

	/**
	 * Get hide branding flag
	 *
	 * @param bool $hide_branding Hide branding.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return bool
	 */
	public function get_hide_branding( $hide_branding = false ) {
		// Only if whitelabel enabled.
		if ( $this->can_whitelabel() ) {
			// Whitelabel settings.
			$settings = $this->get_settings();

			// Get branding enabled settings.
			$hide_branding = (bool) $settings['branding_enabled'];
		}

		return $hide_branding;
	}

	/**
	 * Get Hero Image for branding.
	 *
	 * @param string $hero_image Hero image link.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return string
	 */
	public function get_branding_hero_image( $hero_image = '' ) {
		// Only if whitelabel enabled.
		if ( $this->can_whitelabel() ) {
			// Whitelabel settings.
			$settings = $this->get_settings();

			// Only if branding enabled.
			if ( $settings['branding_enabled'] ) {
				$type = $settings['branding_type'];

				// In network, see if subsite can override using custom logo.
				if ( is_multisite() && ! is_network_admin() && 1 === absint( $settings['branding_enabled_subsite'] ) && 'custom' === $type ) {
					// if custom logo set in subsite.
					if ( has_custom_logo() ) {
						$custom_logo_id = get_theme_mod( 'custom_logo' );
						// Get image url for logo.
						$hero_image = wp_get_attachment_image_url( $custom_logo_id, 'full' );
					}
				} else {
					// Get the image link for network admin or single site.
					$hero_image = 'link' === $type ? $settings['branding_image_link'] : $settings['branding_image'];
				}

				// Set an empty string.
				if ( false === isset( $hero_image ) ) {
					$hero_image = '';
				}
			}
		}

		return $hero_image;
	}

	/**
	 * Check if footer branding is enabled.
	 *
	 * @param bool $change_footer Should change footer.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return bool
	 */
	public function get_branding_change_footer( $change_footer = false ) {
		// Only if whitelabel enabled.
		if ( $this->can_whitelabel() ) {
			// Whitelabel settings.
			$settings = $this->get_settings();

			// Get footer enabled settings.
			$change_footer = (bool) $settings['footer_enabled'];
		}

		return $change_footer;
	}

	/**
	 * Get Footer Text for branding.
	 *
	 * @param string $footer_text Footer custom text.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return string
	 */
	public function get_branding_footer_text( $footer_text ) {
		// Only if whitelabel enabled.
		if ( $this->can_whitelabel() ) {
			// Whitelabel settings.
			$settings = $this->get_settings();
			// Only if footer enabled.
			if ( $settings['footer_enabled'] ) {
				$footer_text = $settings['footer_text'];
			}
		}

		return $footer_text;
	}

	/**
	 * Get docs link enabled status.
	 *
	 * @param bool $hide_doc_link Is doc link hidden.
	 *
	 * @since 4.11.1 Moved to new class.
	 *
	 * @since 4.6
	 * @return bool
	 */
	public function get_branding_hide_doc_link( $hide_doc_link = false ) {
		// Only if whitelabel enabled.
		if ( $this->can_whitelabel() ) {
			// Whitelabel settings.
			$settings = $this->get_settings();

			// Get doc links enabled settings.
			$hide_doc_link = (bool) $settings['doc_links_enabled'];
		}

		return $hide_doc_link;
	}

	/**
	 * Check if whitelabel feature is enabled.
	 *
	 * @since 4.11.1
	 *
	 * @return bool
	 */
	public function is_whitelabel_enabled() {
		// Get whitelabel settings.
		$settings = $this->get_settings();

		return (bool) $settings['enabled'];
	}

	/**
	 * Check if whitelabel feature is allowed for the membership.
	 *
	 * @since 4.11.1
	 *
	 * @return bool
	 */
	public function can_whitelabel() {
		// Can whitelabel.
		$can = $this->is_whitelabel_enabled() && WPMUDEV_Dashboard::$api->is_whitelabel_allowed();

		/**
		 * Filter hook to change whitelabel status.
		 *
		 * Do no use this to check if whitelabel is allowed
		 * for the membership. This function check settings
		 * in addition.
		 *
		 * @param bool $can Can whitelabel.
		 *
		 * @since 4.11.1
		 */
		return apply_filters( 'wpmudev_dashboard_can_whitelabel', $can );
	}

	/**
	 * Get whitelabel settings as array assoc
	 *
	 * This function included default structure for whitelabel settings
	 * Static call allowed as long `WPMUDEV_Dashboard::$settings` initialized
	 *
	 * @param array $structure Optional structure array.
	 *
	 * @see   WPMUDEV_Dashboard_Settings::as_array()
	 *
	 * @since 4.11.1 Moved to new class.
	 * @since 4.5.3
	 * @return array
	 */
	public function get_settings( $structure = array() ) {
		static $settings = null;

		if ( null === $settings ) {
			// Default structure.
			$options = array(
				'enabled'                  => array(
					'option'  => 'enabled',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
				'branding_enabled'         => array(
					'option'  => 'branding_enabled',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
				'branding_type'            => array(
					'option'  => 'branding_type',
					'group'   => 'whitelabel',
					'type'    => 'string',
					'default' => 'default',
				),
				'branding_enabled_subsite' => array(
					'option'  => 'branding_enabled_subsite',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
				'branding_image'           => array(
					'option'  => 'branding_image',
					'group'   => 'whitelabel',
					'type'    => 'string',
					'default' => '',
				),
				'branding_image_id'        => array(
					'option'  => 'branding_image_id',
					'group'   => 'whitelabel',
					'default' => '',
				),
				'branding_image_link'      => array(
					'option'  => 'branding_image_link',
					'group'   => 'whitelabel',
					'default' => '',
				),
				'footer_enabled'           => array(
					'option'  => 'footer_enabled',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
				'footer_text'              => array(
					'option'  => 'footer_text',
					'group'   => 'whitelabel',
					'type'    => 'string',
					'default' => '',
				),
				'labels_enabled'           => array(
					'option'  => 'labels_enabled',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
				'labels_config'            => array(
					'option'  => 'labels_config',
					'group'   => 'whitelabel',
					'type'    => 'array',
					'default' => array(),
				),
				'labels_config_selected'   => array(
					'option'  => 'labels_config_selected',
					'group'   => 'whitelabel',
					'type'    => 'string',
					'default' => '',
				),
				'labels_networkwide'       => array(
					'option'  => 'labels_networkwide',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => true,
				),
				'labels_subsites'          => array(
					'option'  => 'labels_subsites',
					'group'   => 'whitelabel',
					'type'    => 'array',
					'default' => array(),
				),
				'doc_links_enabled'        => array(
					'option'  => 'doc_links_enabled',
					'group'   => 'whitelabel',
					'type'    => 'boolean',
					'default' => false,
				),
			);

			// Merge with structure.
			$options = array_merge( $options, $structure );

			// Get whitelabel settings formatted.
			$settings = WPMUDEV_Dashboard::$settings->as_array( $options );
		}

		return $settings;
	}

	/**
	 * Process menu item and replace label and icon.
	 *
	 * When required, replace menu label and icon based
	 * on the configurations in white label settings.
	 *
	 * @param array $data Menu data.
	 *
	 * @since 4.11.1
	 *
	 * @return array $data
	 */
	private function process_menu( $data ) {
		// Get available configurations.
		$configs = $this->get_menu_configs();

		// Continue only if config for menu found.
		if ( isset( $configs[ $data[2] ] ) ) {
			$config = $configs[ $data[2] ];

			// If label changed.
			if ( ! empty( $config['name'] ) ) {
				// Replace menu label.
				$data[0] = $config['name'];
				// Rename page title.
				$data[3] = $config['name'];
			}

			// Replace menu icon.
			if ( isset( $data[6] ) ) {
				if ( ! empty( $config['icon_type'] ) ) {
					$print_style = false;
					switch ( $config['icon_type'] ) {
						case 'dashicon':
							// Set dashicons class.
							$class = sanitize_html_class( $config['icon_class'] );
							if ( ! empty( $class ) ) {
								$data[6] = 'dashicons-' . $class;
							}
							break;
						case 'upload':
							// Make sure the ID is int.
							$thumbnail_id = (int) $config['thumb_id'];
							// Get thumbnail url.
							$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail', true );
							if ( ! empty( $thumbnail_id ) && ! empty( $thumbnail_url ) ) {
								$print_style = true;
								$data[6]     = $thumbnail_url;
							}
							break;
						case 'link':
							// Set direct link.
							$icon = esc_url( $config['icon_url'] );
							if ( ! empty( $icon ) ) {
								$print_style = true;
								$data[6]     = $icon;
							}
							break;
						case 'none':
							// No icon.
							$data[6] = 'none';
							break;
					}

					// Add a small inline style to fix icon size.
					if ( $print_style && ! has_action( 'admin_print_styles', array( $this, 'print_icon_style' ) ) ) {
						add_action( 'admin_print_styles', array( $this, 'print_icon_style' ), 99 );
					}
				}
			}
		}

		/**
		 * Filter to modify plugin menu data.
		 *
		 * @param array $data Menu data.
		 *
		 * @since 4.11.1
		 */
		return apply_filters( 'wpmudev_dashboard_whitelabel_process_menu', $data );
	}

	/**
	 * Get all available plugin config data.
	 *
	 * Config data is prepared only for the active plugins.
	 * If any of the plugins change their slug if feature,
	 * the whitelabel won't work for that plugin.
	 *
	 * @since 4.11.1
	 *
	 * @return array
	 */
	private function get_menu_configs() {
		static $menu_configs = null;

		if ( null === $menu_configs ) {
			$menu_configs = array();

			// Only if whitelabel enabled.
			if ( $this->can_whitelabel() ) {
				// Get settings.
				$settings = $this->get_settings();

				// Check network settings.
				if ( is_multisite() && ! is_network_admin() && empty( $settings['labels_networkwide'] ) ) {
					// Site IDs.
					$sites = (array) $settings['labels_subsites'];

					// If current subsite is not selected.
					if ( empty( $sites ) || ! in_array( get_current_blog_id(), $sites ) ) { // phpcs:ignore
						// Do not continue if no subsites selected.
						return apply_filters( 'wpmudev_dashboard_whitelabel_menu_configs', $menu_configs );
					}
				}

				// Only if enabled.
				if ( $settings['labels_enabled'] ) {
					// Get configuration.
					$config = (array) $settings['labels_config'];
					// WPMUDEV plugins ID and slug.
					$slugs = $this->get_plugin_slugs();

					// Go through each plugins.
					foreach ( $config as $pid => $data ) {
						// Make sure the ID is int.
						$pid = (int) $pid;
						// Get the slugs for project.
						$plugin_slugs = array_filter( $slugs, function ( $id ) use ( $pid ) {
							return $id === $pid;
						} );

						if ( ! empty( $plugin_slugs ) ) {
							// Add to enabled list.
							foreach ( array_keys( $plugin_slugs ) as $plugin_slug ) {
								$menu_configs[ $plugin_slug ] = $data;
							}
						}
					}
				}
			}
		}

		/**
		 * Filter to modify plugin menu config.
		 *
		 * @param array $plugins Plugins data.
		 *
		 * @since 4.11.1
		 */
		return apply_filters( 'wpmudev_dashboard_whitelabel_menu_configs', $menu_configs );
	}

	/**
	 * Get WPMUDEV plugin IDs and main menu slugs.
	 *
	 * @since 4.11.1
	 * @since 4.11.10 Interchanged keys with values.
	 *
	 * @return mixed|void
	 */
	private function get_plugin_slugs() {
		// WPMUDEV plugins ID and slug.
		$slugs = array(
			'beehive'              => 51,
			'wpmudev'              => 119,
			'wds_wizard'           => 167,
			'wds_network_settings' => 167,
			'wpmudev-videos'       => 248,
			'branding'             => 9135,
			'smush'                => 912164,
			'wphb'                 => 1081721,
			'wp-defender'          => 1081723,
			'hustle'               => 1107020,
			'forminator'           => 2097296,
			'shipper'              => 2175128,
			'snapshot'             => 3760011,
			'wpmudev-hub'          => 3779636,
		);

		/**
		 * Filter to modify plugin slugs list.
		 *
		 * @param array $slugs Slugs.
		 *
		 * @since 4.11.1
		 */
		return apply_filters( 'wpmudev_dashboard_whitelabel_plugin_slugs', $slugs );
	}

	/**
	 * Wrapper function to get main site ID.
	 *
	 * Function get_main_site_id() is introduced in 4.9.0. So we need
	 * to make sure we get the current id even in old versions.
	 *
	 * @since 4.11.1
	 *
	 * @return int
	 */
	private function main_site_id() {
		// Use get_main_site_id if it is available.
		if ( function_exists( 'get_main_site_id' ) ) {
			return get_main_site_id();
		}

		// If not multisite, return current ID.
		if ( ! is_multisite() ) {
			return get_current_blog_id();
		}

		// Get the network.
		$network = get_network();

		return $network ? $network->site_id : 0;
	}
}
