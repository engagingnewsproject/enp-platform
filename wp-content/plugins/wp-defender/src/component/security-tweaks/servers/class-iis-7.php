<?php
/**
 * Responsible for managing server configurations related to security tweaks .
 *
 * @package WP_Defender\Component\Security_Tweaks\Servers
 */

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;
use DOMXPath;
use DOMDocument;
use DOMException;

/**
 * Provides methods to apply and revert security rules on servers.
 */
class IIS_7 {

	/**
	 * New htaccess file.
	 *
	 * @var array
	 */
	private $new_htaccess_config = array();

	/**
	 * Service type.
	 *
	 * @var string
	 */
	private $type = null;

	/**
	 * Constructor for class.
	 *
	 * @param  string $type  The type of the security tweak.
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		$url = '';

		if ( 'prevent-php-executed' === $this->type ) {
			$dir = wp_upload_dir();
			$url = $dir['baseurl'] . '/wp-defender/index.php';
		}

		if ( 'protect-information' === $this->type ) {
			$url = defender_asset_url( '/languages/' . WP_DEFENDER_POT_FILENAME );
		}

		return Server::ping_test_failed( $url );
	}

	/**
	 * Process the rule.
	 *
	 * @return bool|WP_Error
	 * @throws DOMException If invalid $localName.
	 */
	public function process() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$path     = WP_CONTENT_DIR . '/uploads';
		$filename = 'web.config';
		if ( ! file_exists( $path . '/' . $filename ) ) {
			$wp_filesystem->put_contents( $path . '/' . $filename, '<configuration/>' );
		}
		$formatxml  = PHP_EOL;
		$formatxml .= '  <handlers accessPolicy="Read" />';
		$formatxml .= PHP_EOL;

		$doc = new DOMDocument();
		// This property is belongs to DOMDocument. So we can ignore the warning.
		$doc->preserveWhiteSpace = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( $doc->load( $path . '/' . $filename ) === false ) {
			return new WP_Error(
				'defender_file_not_editable',
				sprintf(
				/* translators: %s: File name. */
					esc_html__( 'The file %s could not be loaded.', 'wpdef' ),
					$filename
				)
			);
		}

		$xpath             = new DOMXPath( $doc );
		$read_accesspolicy = $xpath->query( '/configuration/system.webServer/handlers[starts-with(@accessPolicy,\'Read\')]' );

		if ( $read_accesspolicy->length > 0 ) {
			return true;
		}

		$xmlnodes = $xpath->query( '/configuration/system.webServer/handlers' );

		if ( $xmlnodes->length > 0 ) {
			$handlers_node = $xmlnodes->item( 0 );
		} else {
			$handlers_node = $doc->createElement( 'handlers' );
			$xmlnodes      = $xpath->query( '/configuration/system.webServer' );

			if ( $xmlnodes->length > 0 ) {
				$system_web_server_node = $xmlnodes->item( 0 );
				$handler_fragment       = $doc->createDocumentFragment();
				$handler_fragment->appendXML( $formatxml );
				$system_web_server_node->appendChild( $handler_fragment );
			} else {
				$system_web_server_node = $doc->createElement( 'system.webServer' );
				$handler_fragment       = $doc->createDocumentFragment();
				$handler_fragment->appendXML( $formatxml );
				$system_web_server_node->appendChild( $handler_fragment );
				$xmlnodes = $xpath->query( '/configuration' );

				if ( $xmlnodes->length > 0 ) {
					$config_node = $xmlnodes->item( 0 );
					$config_node->appendChild( $system_web_server_node );
				} else {
					$config_node = $doc->createElement( 'configuration' );
					$doc->appendChild( $config_node );
					$config_node->appendChild( $system_web_server_node );
				}
			}
		}

		$rule_fragment = $doc->createDocumentFragment();
		$rule_fragment->appendXML( $formatxml );
		$handlers_node->appendChild( $rule_fragment );

		$doc->encoding     = 'UTF-8';
		$doc->formatOutput = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		saveDomDocument( $doc, $path . '/' . $filename );

		$settings = array(
			'new_htaccess_config' => $this->get_new_htaccess_config(),
		);

		return update_site_option( "defender_security_tweeks_{$this->type}", $settings );
	}

	/**
	 * Revert the rule.
	 *
	 * @return bool
	 */
	public function revert() {
		$path     = WP_CONTENT_DIR . '/uploads';
		$filename = 'web.config';

		if ( ! file_exists( $path . '/' . $filename ) ) {
			return true;
		}

		$doc                     = new DOMDocument();
		$doc->preserveWhiteSpace = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( $doc->load( $path . '/' . $filename ) === false ) {
			return false;
		}

		$xpath    = new DOMXPath( $doc );
		$handlers = $xpath->query( '/configuration/system.webServer/handlers[contains(@accessPolicy,\'Read\')]' );

		if ( $handlers->length > 0 ) {
			$child  = $handlers->item( 0 );
			$parent = $child->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$parent->removeChild( $child );
			$doc->formatOutput = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			saveDomDocument( $doc, $path . '/' . $filename );
		}

		return true;
	}

	/**
	 * Get the new HT config.
	 *
	 * @return array
	 */
	public function get_new_htaccess_config() {
		return $this->new_htaccess_config;
	}
}