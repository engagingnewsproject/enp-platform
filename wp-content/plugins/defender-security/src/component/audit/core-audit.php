<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use WP_Defender\Model\Audit_Log;
use WP_Upgrader;

class Core_Audit extends Audit_Event {
	public const ACTION_ACTIVATED = 'activated', ACTION_DEACTIVATED = 'deactivated', ACTION_INSTALLED = 'installed', ACTION_UPGRADED = 'upgraded';
	public const FILE_ADDED = 'file_added', FILE_MODIFIED = 'file_modified';
	public const CONTEXT_THEME = 'ct_theme', CONTEXT_PLUGIN = 'ct_plugin', CONTEXT_CORE = 'ct_core';

	/**
	 * @return array
	 */
	public function get_hooks(): array {
		$data = [
			'switch_theme' => [
				'args' => [ 'new_name', 'new_theme' ],
				'callback' => [ self::class, 'process_activate_theme' ],
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
				'action_type' => self::ACTION_ACTIVATED,
			],
			'activated_plugin' => [
				'args' => [ 'plugin' ],
				'text' => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
					esc_html__( '%1$s %2$s activated plugin: %3$s, version %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{plugin_name}}',
					'{{plugin_version}}'
				),
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
				'action_type' => self::ACTION_ACTIVATED,
				'context' => self::CONTEXT_PLUGIN,
				'program_args' => [
					'plugin_abs_path' => [
						'callable' => [ self::class, 'get_plugin_abs_path' ],
						'params' => [
							'{{plugin}}',
						],
					],
					'plugin_name' => [
						'callable' => 'get_plugin_data',
						'params' => [
							'{{plugin_abs_path}}',
						],
						'result_property' => 'Name',
					],
					'plugin_version' => [
						'callable' => 'get_plugin_data',
						'params' => [
							'{{plugin_abs_path}}',
						],
						'result_property' => 'Version',
					],
				],
			],
			'deleted_plugin' => [
				'args' => [ 'plugin_file', 'deleted' ],
				'callback' => [ self::class, 'process_delete_plugin' ],
				'action_type' => self::ACTION_DEACTIVATED,
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
			],
			'deactivated_plugin' => [
				'args' => [ 'plugin' ],
				'text' => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
					esc_html__( '%1$s %2$s deactivated plugin: %3$s, version %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{plugin_name}}',
					'{{plugin_version}}'
				),
				'action_type' => self::ACTION_DEACTIVATED,
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
				'context' => self::CONTEXT_PLUGIN,
				'program_args' => [
					'plugin_abs_path' => [
						'callable' => [ self::class, 'get_plugin_abs_path' ],
						'params' => [
							'{{plugin}}',
						],
					],
					'plugin_name' => [
						'callable' => 'get_plugin_data',
						'params' => [
							'{{plugin_abs_path}}',
						],
						'result_property' => 'Name',
					],
					'plugin_version' => [
						'callable' => 'get_plugin_data',
						'params' => [
							'{{plugin_abs_path}}',
						],
						'result_property' => 'Version',
					],
				],
			],
			'upgrader_process_complete' => [
				'args' => [ 'upgrader', 'options' ],
				'callback' => [ self::class, 'process_installer' ],
				'action_type' => self::ACTION_UPGRADED,
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
			],
		];

		global $wp_version;
		// @since 2.7.0 Add hook for deleted theme. Use 'deleted_theme' hook that was added since WP v5.8.0.
		if ( version_compare( $wp_version, '5.8.0', '>=' ) ) {
			$data['deleted_theme'] = [
				'args' => [ 'stylesheet', 'deleted' ],
				'text' => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Stylesheet of the theme. */
					esc_html__( '%1$s %2$s deleted theme: %3$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{stylesheet}}'
				),
				'action_type' => self::ACTION_DEACTIVATED,
				'event_type' => Audit_Log::EVENT_TYPE_SYSTEM,
				'context' => self::CONTEXT_THEME,
			];
		}

		return $data;
	}

	/**
	 * @return array|bool
	 */
	public function upgrade_core() {
		$update_core = get_site_transient( 'update_core' );
		if ( is_object( $update_core ) ) {
			$updates = $update_core->updates;
			$updates = array_shift( $updates );
			if ( is_object( $updates ) && property_exists( $updates, 'version' ) ) {
				$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

				return [
					sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Wordpress version. */
						esc_html__( '%1$s %2$s updated WordPress to %3$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						$updates->version
					),
					self::CONTEXT_CORE,
				];
			}
		}

		return false;
	}

	/**
	 * @param WP_Upgrader $upgrader
	 * @param array $options
	 *
	 * @return array|bool|void
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
			$texts = [];
			foreach ( $options['themes'] as $slug ) {
				$theme = wp_get_theme( $slug );
				if ( is_object( $theme ) ) {
					$texts[] = sprintf(
					/* translators: 1: Theme name, 2: Theme version. */
						$failed_result ? esc_html( '%1$s version %2$s' ) : esc_html( '%1$s to %2$s' ),
						$theme->Name,
						$theme->get( 'Version' )
					);
				}
			}
			if ( count( $texts ) ) {
				return [
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
				];
			} else {
				return false;
			}
		} elseif ( 'plugin' === $options['type'] ) {
			$texts = [];
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
				return [
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
				];
			} else {
				return false;
			}
		}
	}

	/**
	 * @param WP_Upgrader $upgrader
	 * @param array $options
	 *
	 * @return array|bool|void
	 */
	public function single_upgrade( WP_Upgrader $upgrader, array $options ) {
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		if ( 'theme' === $options['type'] ) {
			$theme = wp_get_theme( $options['theme'] );
			if ( is_object( $theme ) ) {
				$name = $theme->Name;
				$version = $theme->get( 'Version' );

				return [
					sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Theme name, 4: Theme version. */
						esc_html__( '%1$s %2$s updated theme: %3$s, version %4$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						$name,
						$version
					),
					self::CONTEXT_THEME,
				];
			} else {
				return false;
			}
		} elseif ( 'plugin' === $options['type'] ) {
			$slug = $options['plugin'];
			$data = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
			if ( is_array( $data ) ) {
				$name = $data['Name'];
				$version = $data['Version'];

				return [
					sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Plugin name, 4: Plugin version. */
						esc_html__( '%1$s %2$s updated plugin: %3$s, version %4$s', 'wpdef' ),
						$blog_name,
						$this->get_source_of_action(),
						$name,
						$version
					),
					self::CONTEXT_PLUGIN,
				];
			} else {
				return false;
			}
		}
	}

	/**
	 * Install process.
	 * Log in the format: {BLOG_NAME} {USERNAME} installed {theme/plugin}: {theme/plugin name}, version {VERSION}.
	 *
	 * @param WP_Upgrader $upgrader
	 * @param array $options
	 *
	 * @return bool|array
	*/
	private function single_install( WP_Upgrader $upgrader, array $options ) {
		if ( ! is_object( $upgrader->skin ) ) {
			return false;
		}
		// Only for plugins, themes. No for translation, core.
		if ( ! in_array( $options['type'], [ 'theme', 'plugin' ], true ) ) {
			return false;
		}

		$name = '';
		if ( @is_object( $upgrader->skin->api ) ) {// phpcs:ignore
			$name = $upgrader->skin->api->name;
		} elseif ( ! empty( $upgrader->skin->upgrader) ) {
			$type_data = ( 'theme' === $options['type'] && isset( $upgrader->skin->upgrader->new_theme_data ) )
				? $upgrader->skin->upgrader->new_theme_data
				: $upgrader->skin->upgrader->new_plugin_data;
			if ( ! empty( $type_data['Name'] ) && ! empty( $type_data['Version'] ) ) {
				$name = $type_data['Name'] . ', version ' . $type_data['Version'];
			}
		} elseif ( ! empty( $upgrader->skin->result ) ) {
			if ( is_array( $upgrader->skin->result ) && isset( $upgrader->skin->result['destination_name'] ) ) {
				$name = $upgrader->skin->result['destination_name'];
			} elseif ( is_object( $upgrader->skin->result ) && property_exists( $upgrader->skin->result, 'destination_name' ) ) {
				$name = $upgrader->skin->result->destination_name;
			}
		}

		if ( empty( $name ) ) {
			return false;
		}

		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		if ( 'theme' === $options['type'] ) {
			return [
				sprintf(
				/* translators: %s - blog name, %s - username, %s - theme name */
					esc_html__( '%1$s %2$s installed theme: %3$s', 'wpdef' ),
					$blog_name,
					$this->get_source_of_action(),
					$name
				),
				self::CONTEXT_THEME,
				self::ACTION_INSTALLED,
			];
		} else {
			return [
				sprintf(
				/* translators: %s - blog name, %s - username, %s - plugin name */
					esc_html__( '%1$s %2$s installed plugin: %3$s', 'wpdef' ),
					$blog_name,
					$this->get_source_of_action(),
					$name
				),
				self::CONTEXT_PLUGIN,
				self::ACTION_INSTALLED,
			];
		}

		return false;
	}

	/**
	 * Param 'upgrader' is WP_Upgrader instance. It might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
	 * Param 'options' is array of bulk item update data. Keys:
	 * 'action' (string) Type of action. Default 'update'.
	 * 'type' (string) Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
	 * 'bulk' (bool) Whether the update process is a bulk update. Default true.
	 * 'plugins' (array) Array of the basename paths of the plugins' main files.
	 * 'themes' (array) The theme slugs.
	 * 'translations' (array) Array of translations update data: 'language', 'type', 'slug', 'version'.
	 *
	 * @return mixed|void
	 */
	public function process_installer() {
		$args = func_get_args();
		$upgrader = $args[1]['upgrader'];
		$options = $args[1]['options'];
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
		$args = func_get_args();
		$new_theme = $args[1]['new_theme'];
		if ( ! is_object( $new_theme ) ) {
			return false;
		}
		$new_name = $args[1]['new_name'];
		$version = $new_theme->get( 'Version' );
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return [
			sprintf(
			/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Theme name, 4: Theme version. */
				esc_html__( '%1$s %2$s activated theme: %3$s, version %4$s', 'wpdef' ),
				$blog_name,
				$this->get_source_of_action(),
				$new_name,
				$version
			),
			self::CONTEXT_THEME,
		];
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
		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return [
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
		];
	}

	/**
	 * @return array
	 */
	public function dictionary(): array {
		return [
			self::ACTION_DEACTIVATED => esc_html__( 'deactivated', 'wpdef' ),
			self::ACTION_UPGRADED => esc_html__( 'upgraded', 'wpdef' ),
			self::ACTION_ACTIVATED => esc_html__( 'activated', 'wpdef' ),
			self::ACTION_INSTALLED => esc_html__( 'installed', 'wpdef' ),
			self::CONTEXT_THEME => esc_html__( 'theme', 'wpdef' ),
			self::CONTEXT_PLUGIN => esc_html__( 'plugin', 'wpdef' ),
			self::CONTEXT_CORE => esc_html__( 'WordPress', 'wpdef' ),
			self::FILE_ADDED => esc_html__( 'File Added', 'wpdef' ),
			self::FILE_MODIFIED => esc_html__( 'File Modified', 'wpdef' ),
		];
	}

	/**
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_plugin_abs_path( string $slug ): string {
		if ( ! is_file( $slug ) ) {
			$slug = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug;
		}

		return $slug;
	}
}
