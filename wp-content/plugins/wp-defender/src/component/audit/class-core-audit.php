<?php
/**
 * Represents an audit system for WordPress core, themes, and plugins.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use WP_Upgrader;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Component\Security_Tweaks\WP_Version;

/**
 * Handles the auditing of WordPress core, themes, and plugins such as plugin activation, deactivation, and
 * installation, and theme activation, deactivation, and installation.
 */
class Core_Audit extends Audit_Event {

	public const ACTION_ACTIVATED = 'activated', ACTION_DEACTIVATED = 'deactivated', ACTION_INSTALLED = 'installed', ACTION_UPGRADED = 'upgraded';
	public const FILE_ADDED       = 'file_added', FILE_MODIFIED = 'file_modified';
	public const CONTEXT_THEME    = 'ct_theme', CONTEXT_PLUGIN = 'ct_plugin', CONTEXT_CORE = 'ct_core';

	/**
	 * Retrieves a list of hooks that the audit system will respond to.
	 *
	 * @return array Associative array of hooks and their corresponding callback information.
	 */
	public function get_hooks(): array {
		$data = array(
			'switch_theme'              => array(
				'args'        => array( 'new_name', 'new_theme' ),
				'callback'    => array( self::class, 'process_activate_theme' ),
				'event_type'  => Audit_Log::EVENT_TYPE_SYSTEM,
				'action_type' => self::ACTION_ACTIVATED,
			),
			'activated_plugin'          => array(
				'args'         => array( 'plugin' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
					esc_html__( '%1$s %2$s activated plugin: %3$s, version %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{plugin_name}}',
					'{{plugin_version}}'
				),
				'event_type'   => Audit_Log::EVENT_TYPE_SYSTEM,
				'action_type'  => self::ACTION_ACTIVATED,
				'context'      => self::CONTEXT_PLUGIN,
				'program_args' => array(
					'plugin_abs_path' => array(
						'callable' => array( self::class, 'get_plugin_abs_path' ),
						'params'   => array(
							'{{plugin}}',
						),
					),
					'plugin_name'     => array(
						'callable'        => 'get_plugin_data',
						'params'          => array(
							'{{plugin_abs_path}}',
						),
						'result_property' => 'Name',
					),
					'plugin_version'  => array(
						'callable'        => 'get_plugin_data',
						'params'          => array(
							'{{plugin_abs_path}}',
						),
						'result_property' => 'Version',
					),
				),
			),
			'deleted_plugin'            => array(
				'args'        => array( 'plugin_file', 'deleted' ),
				'callback'    => array( self::class, 'process_delete_plugin' ),
				'action_type' => self::ACTION_DEACTIVATED,
				'event_type'  => Audit_Log::EVENT_TYPE_SYSTEM,
			),
			'deactivated_plugin'        => array(
				'args'         => array( 'plugin' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
					esc_html__( '%1$s %2$s deactivated plugin: %3$s, version %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{plugin_name}}',
					'{{plugin_version}}'
				),
				'action_type'  => self::ACTION_DEACTIVATED,
				'event_type'   => Audit_Log::EVENT_TYPE_SYSTEM,
				'context'      => self::CONTEXT_PLUGIN,
				'program_args' => array(
					'plugin_abs_path' => array(
						'callable' => array( self::class, 'get_plugin_abs_path' ),
						'params'   => array(
							'{{plugin}}',
						),
					),
					'plugin_name'     => array(
						'callable'        => 'get_plugin_data',
						'params'          => array(
							'{{plugin_abs_path}}',
						),
						'result_property' => 'Name',
					),
					'plugin_version'  => array(
						'callable'        => 'get_plugin_data',
						'params'          => array(
							'{{plugin_abs_path}}',
						),
						'result_property' => 'Version',
					),
				),
			),
			'upgrader_process_complete' => array(
				'args'        => array( 'upgrader', 'options' ),
				'callback'    => array( self::class, 'process_installer' ),
				'action_type' => self::ACTION_UPGRADED,
				'event_type'  => Audit_Log::EVENT_TYPE_SYSTEM,
			),
		);

		global $wp_version;
		// @since 2.7.0 Add hook for deleted theme. Use 'deleted_theme' hook that was added since WP v5.8.0.
		if ( version_compare( $wp_version, '5.8.0', '>=' ) ) {
			$data['deleted_theme'] = array(
				'args'        => array( 'stylesheet', 'deleted' ),
				'text'        => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Stylesheet of the theme. */
					esc_html__( '%1$s %2$s deleted theme: %3$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{stylesheet}}'
				),
				'action_type' => self::ACTION_DEACTIVATED,
				'event_type'  => Audit_Log::EVENT_TYPE_SYSTEM,
				'context'     => self::CONTEXT_THEME,
			);
		}

		return $data;
	}

	/**
	 * Prepares the data for recording a core upgrade event.
	 *
	 * @param  string $wp_version  The new WordPress version.
	 *
	 * @return array Array containing the formatted message and the context.
	 */
	private function get_upgraded_core_data_for_record( $wp_version ) {
		return array(
			sprintf(
			/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Wordpress version. */
				esc_html__( '%1$s %2$s updated WordPress to %3$s', 'wpdef' ),
				is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '',
				$this->get_source_of_action(),
				$wp_version
			),
			self::CONTEXT_CORE,
		);
	}

	/**
	 * Handles the upgrade process for WordPress core and prepares the audit log entry.
	 *
	 * @return array|bool Returns the log entry data or false if no update is needed.
	 */
	public function upgrade_core() {
		$update_core = get_site_transient( 'update_core' );
		if ( is_object( $update_core ) ) {
			$updates = $update_core->updates;
			$updates = array_shift( $updates );
			if ( is_object( $updates ) && property_exists( $updates, 'version' ) ) {
				return $this->get_upgraded_core_data_for_record( $updates->version );
			}
		} elseif ( empty( $update_core ) && $this->is_hub_request() ) {
			// Hub has already updated WP core to the latest version.
			$wp_version = ( new WP_Version() )->get_latest_version();

			return $wp_version ? $this->get_upgraded_core_data_for_record( $wp_version ) : false;
		}

		return false;
	}

	/**
	 * Processes bulk upgrade actions for themes or plugins and generates the audit log entry.
	 *
	 * @param  WP_Upgrader $upgrader  The upgrade instance.
	 * @param  array       $options  Options containing type and items being upgraded.
	 *
	 * @return array|bool Returns the log entry data or false if unable to generate log data.
	 */
	public function bulk_upgrade( WP_Upgrader $upgrader, array $options ) {
		if ( ! is_object( $upgrader->skin ) ) {
			return false;
		}
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		if ( isset( $upgrader->skin->result ) && is_wp_error( $upgrader->skin->result ) ) {
			// Todo: extend Audit table with a new column for the result of the process and divide it into success and failure.
			$failed_result = true;
		} else {
			$failed_result = false;
		}

		if ( 'theme' === $options['type'] ) {
			$texts = array();
			foreach ( $options['themes'] as $slug ) {
				$theme = wp_get_theme( $slug );
				if ( is_object( $theme ) ) {
					$texts[] = sprintf(
					/* translators: 1: Theme name, 2: Theme version. */
						$failed_result ? esc_html( '%1$s version %2$s' ) : esc_html( '%1$s to %2$s' ),
						$theme->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$theme->get( 'Version' )
					);
				}
			}
			if ( count( $texts ) ) {
				return array(
					sprintf(
						$failed_result ?
							/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Translated status for themes. */
							esc_html__( '%1$s %2$s was unable to update themes: %3$s', 'wpdef' ) :
							/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Translated status for themes. */
							esc_html__( '%1$s %2$s updated themes: %3$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						implode( ', ', $texts )
					),
					self::CONTEXT_THEME,
				);
			} else {
				return false;
			}
		} elseif ( 'plugin' === $options['type'] ) {
			$texts = array();
			foreach ( $options['plugins'] as $slug ) {
				$plugin = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
				if ( is_array( $plugin ) && isset( $plugin['Name'] ) && ! empty( $plugin['Name'] ) ) {
					$texts[] = sprintf(
					/* translators: 1: Plugin name, 2: Plugin version. */
						$failed_result ? esc_html( '%1$s version %2$s' ) : esc_html( '%1$s to %2$s' ),
						$plugin['Name'],
						$plugin['Version']
					);
				}
			}
			if ( count( $texts ) ) {
				return array(
					sprintf(
						$failed_result ?
							/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Translated status for plugins. */
							esc_html__( '%1$s %2$s was unable to update plugins: %3$s', 'wpdef' ) :
							/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Translated status for plugins. */
							esc_html__( '%1$s %2$s updated plugins: %3$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						implode( ', ', $texts )
					),
					self::CONTEXT_PLUGIN,
				);
			} else {
				return false;
			}
		}
	}

	/**
	 * Handles single item upgrade for themes or plugins and prepares the audit log entry.
	 *
	 * @param  WP_Upgrader $upgrader  The upgrade instance.
	 * @param  array       $options  Options containing type and the item being upgraded.
	 *
	 * @return array|bool Returns the log entry data or false if unable to generate log data.
	 */
	public function single_upgrade( WP_Upgrader $upgrader, array $options ) {
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		if ( 'theme' === $options['type'] ) {
			$theme = wp_get_theme( $options['theme'] );
			if ( is_object( $theme ) ) {
				$name    = $theme->Name; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$version = $theme->get( 'Version' );

				return array(
					sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Theme name, 4: Theme version. */
						esc_html__( '%1$s %2$s updated theme: %3$s, version %4$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						$name,
						$version
					),
					self::CONTEXT_THEME,
				);
			} else {
				return false;
			}
		} elseif ( 'plugin' === $options['type'] ) {
			$slug = $options['plugin'];
			$data = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
			if ( is_array( $data ) ) {
				$name    = $data['Name'];
				$version = $data['Version'];

				return array(
					sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
						esc_html__( '%1$s %2$s updated plugin: %3$s, version %4$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						$name,
						$version
					),
					self::CONTEXT_PLUGIN,
				);
			} else {
				return false;
			}
		}
	}

	/**
	 * Install process.
	 * Log in the format: {BLOG_NAME} {USERNAME} installed {theme/plugin}: {theme/plugin name}, version {VERSION}.
	 *
	 * @param  WP_Upgrader $upgrade  The upgrade instance.
	 * @param  array       $options  Options containing type and the item being installed.
	 *
	 * @return bool|array
	 */
	private function single_install( WP_Upgrader $upgrade, array $options ) {
		if ( ! is_object( $upgrade->skin ) ) {
			return false;
		}
		// Only for plugins, themes. No for translation, core.
		if ( ! in_array( $options['type'], array( 'theme', 'plugin' ), true ) ) {
			return false;
		}

		$name = '';
		if ( ! empty( $upgrade->skin->api ) ) {
			$name = $upgrade->skin->api->name;
		} elseif ( ! empty( $upgrade->skin->upgrader ) ) {
			$type_data = ( 'theme' === $options['type'] && isset( $upgrade->skin->upgrader->new_theme_data ) )
				? $upgrade->skin->upgrader->new_theme_data
				: $upgrade->skin->upgrader->new_plugin_data;
			if ( ! empty( $type_data['Name'] ) && ! empty( $type_data['Version'] ) ) {
				$name = $type_data['Name'] . ', version ' . $type_data['Version'];
			}
		} elseif ( ! empty( $upgrade->skin->result ) ) {
			if ( is_array( $upgrade->skin->result ) && isset( $upgrade->skin->result['destination_name'] ) ) {
				$name = $upgrade->skin->result['destination_name'];
			} elseif ( is_object( $upgrade->skin->result ) && property_exists(
				$upgrade->skin->result,
				'destination_name'
			) ) {
				$name = $upgrade->skin->result->destination_name;
			}
		}

		if ( empty( $name ) ) {
			return false;
		}

		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		if ( 'theme' === $options['type'] ) {
			return array(
				sprintf(
				/* translators: %s - blog name, %s - username, %s - theme name */
					esc_html__( '%1$s %2$s installed theme: %3$s', 'wpdef' ),
					$blog_name,
					$this->get_source_of_action(),
					$name
				),
				self::CONTEXT_THEME,
				self::ACTION_INSTALLED,
			);
		} else {
			return array(
				sprintf(
				/* translators: %s - blog name, %s - username, %s - plugin name */
					esc_html__( '%1$s %2$s installed plugin: %3$s', 'wpdef' ),
					$blog_name,
					$this->get_source_of_action(),
					$name
				),
				self::CONTEXT_PLUGIN,
				self::ACTION_INSTALLED,
			);
		}

		return false;
	}

	/**
	 * Param 'upgrader' is WP_Upgrader instance. It might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or
	 * Language_Pack_Upgrader instance. Param 'options' is array of bulk item update data. Keys:
	 * 'action' (string) Type of action. Default 'update'.
	 * 'type' (string) Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
	 * 'bulk' (bool) Whether the update process is a bulk update. Default true.
	 * 'plugins' (array) Array of the basename paths of the plugins' main files.
	 * 'themes' (array) The theme slugs.
	 * 'translations' (array) Array of translations update data: 'language', 'type', 'slug', 'version'.
	 *
	 * @return mixed
	 */
	public function process_installer() {
		$args     = func_get_args();
		$upgrader = $args[1]['upgrader'];
		$options  = $args[1]['options'];
		if ( 'core' === $options['type'] ) {
			return $this->upgrade_core();
			// If this is core, we just create text and return.
		} elseif ( isset( $options['bulk'] ) && true === $options['bulk'] ) {
			// Case: local install/update work with 'bulk' => true (for mass action and no).
			return $this->bulk_upgrade( $upgrader, $options );
		} elseif ( 'install' === $options['action'] ) {
			// Case: actions from Hub.
			return $this->single_install( $upgrader, $options );
		} elseif ( 'update' === $options['action'] ) {
			// Case: actions from Hub.
			return $this->single_upgrade( $upgrader, $options );
		}
	}

	/**
	 * Fires after the theme is switched.
	 *
	 * @return array|bool
	 */
	public function process_activate_theme() {
		$args      = func_get_args();
		$new_theme = $args[1]['new_theme'];
		if ( ! is_object( $new_theme ) ) {
			return false;
		}
		$new_name  = $args[1]['new_name'];
		$version   = $new_theme->get( 'Version' );
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return array(
			sprintf(
			/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Theme name, 4: Theme version. */
				esc_html__( '%1$s %2$s activated theme: %3$s, version %4$s', 'wpdef' ),
				$blog_name,
				$this->get_source_of_action(),
				$new_name,
				$version
			),
			self::CONTEXT_THEME,
		);
	}

	/**
	 * Fires immediately after a plugin deletion.
	 * There is no way to get the plugin data (name, version) because it has been removed. Only slug.
	 *
	 * @return array
	 */
	public function process_delete_plugin(): array {
		$args = func_get_args();

		// If 'deleted'-arg is false then the plugin deletion wasn't successful.
		if ( empty( $args[1]['deleted'] ) ) {
			// Todo: extend Audit table with a new column for the result of the process and divide it into success and failure.
			$failed_result = true;
		} else {
			$failed_result = false;
		}
		$plugin_file = $args[1]['plugin_file'];
		$blog_name   = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return array(
			sprintf(
				$failed_result ?
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin file. */
					esc_html__( '%1$s %2$s was unable to delete plugin: %3$s', 'wpdef' ) :
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin file. */
					esc_html__( '%1$s %2$s deleted plugin: %3$s', 'wpdef' ),
				$blog_name,
				$this->get_source_of_action(),
				$plugin_file
			),
			self::CONTEXT_PLUGIN,
		);
	}

	/**
	 * Provides a dictionary of audit event types and their descriptions.
	 *
	 * @return array Associative array of event keys and their descriptions.
	 */
	public function dictionary(): array {
		return array(
			self::ACTION_DEACTIVATED => esc_html__( 'deactivated', 'wpdef' ),
			self::ACTION_UPGRADED    => esc_html__( 'upgraded', 'wpdef' ),
			self::ACTION_ACTIVATED   => esc_html__( 'activated', 'wpdef' ),
			self::ACTION_INSTALLED   => esc_html__( 'installed', 'wpdef' ),
			self::CONTEXT_THEME      => esc_html__( 'theme', 'wpdef' ),
			self::CONTEXT_PLUGIN     => esc_html__( 'plugin', 'wpdef' ),
			self::CONTEXT_CORE       => esc_html__( 'WordPress', 'wpdef' ),
			self::FILE_ADDED         => esc_html__( 'File Added', 'wpdef' ),
			self::FILE_MODIFIED      => esc_html__( 'File Modified', 'wpdef' ),
		);
	}

	/**
	 * Retrieves the absolute path of a plugin.
	 *
	 * @param  string $slug  The plugin slug or relative path.
	 *
	 * @return string The absolute path of the plugin.
	 */
	public function get_plugin_abs_path( string $slug ): string {
		if ( ! is_file( $slug ) ) {
			$slug = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug;
		}

		return $slug;
	}
}