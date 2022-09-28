<?php
declare( strict_types = 1 );

namespace WP_Defender\Traits;

trait Plugin {

	/**
	 * Get all installed plugins.
	 *
	 * @return array
	 */
	public function get_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// WordPress caches this internally.
		return get_plugins();
	}

	/**
	 * Get all slugs.
	 *
	 * @return array
	 */
	public function get_plugin_slugs(): array {
		$slugs = [];
		foreach ( $this->get_plugins() as $slug => $plugin ) {
			$base_slug = explode( '/', $slug );
			$slugs[] = array_shift( $base_slug );
		}

		return $slugs;
	}

	/**
	 * Get plugin data by slug.
	 *
	 * @param string $plugin_slug
	 *
	 * @return array
	 */
	public function get_plugin_data( $plugin_slug ): array {
		foreach ( $this->get_plugins() as $slug => $plugin ) {
			if ( $plugin_slug === $slug ) {
				return $plugin;
			}
		}

		return [];
	}

	/**
	 * @return string
	 */
	public function get_plugin_base_dir(): string {
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			return wp_normalize_path( WP_PLUGIN_DIR . '/' );
		}

		return wp_normalize_path( WP_CONTENT_DIR . '/plugins/' );
	}

	/**
	 * Does the plugin exist on wp.org?
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function check_plugin_on_wp_org( $slug ): bool {
		$url = 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json';
		$http_args = [
			'timeout' => 15,
			'sslverify' => false, // Many hosts have no updated CA bundle.
			'user-agent' => 'Defender/' . DEFENDER_VERSION,
		];
		$response = wp_remote_get( $url, $http_args );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$results = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $results ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check the resulting plugin slug against WordPress.org plugin rules.
	 *
	 * @param string $slug Plugin folder name.
	 *
	 * @return bool
	 */
	public function is_likely_wporg_slug( $slug ): bool {
		// Does file readme.txt exist?
		$readme_file = $this->get_plugin_base_dir() . $slug . '/readme.txt';
		if ( file_exists( $readme_file ) && is_readable( $readme_file ) ) {
			$contents = trim( file_get_contents( $readme_file ) );

			if ( false !== strpos( $contents, '===' ) ) {
				return true;
			}

			if ( false !== strpos( $contents, '#' ) ) {
				return true;
			}
		}

		return false;
	}
}
