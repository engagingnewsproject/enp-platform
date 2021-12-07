<?php

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;
use DOMXPath;
use DOMDocument;

class IIS_7 {
    /**
     * New htaccess file.
     *
     * @var array
     */
    private $new_htaccess_config = [];

    /**
     * Service type.
     *
     * @var string
     */
	private $type = null;

    /**
     * Constructor method.
     *
     * @param void
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
            $url = defender_asset_url( '/languages/wpdef-default.pot' );
        }

        return Server::ping_test_failed( $url );
    }

    /**
     * Process the rule.
     *
     * @return bool
     */
    public function process() {
        $path     = WP_CONTENT_DIR . '/uploads';
        $filename = 'web.config';

        if ( ! file_exists( $path . '/' . $filename ) ) {
            $fp = fopen( $path . '/' . $filename, 'w' );
            fwrite( $fp, '<configuration/>' );
            fclose( $fp );
        }

        $formatxml = PHP_EOL;
        $formatxml .= "  <handlers accessPolicy=\"Read\" />";
        $formatxml .= PHP_EOL;

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;

        if ( $doc->load( $path . '/' . $filename ) === false ) {
            return new WP_Error(
                'defender_file_not_editable',
                sprintf(
                    __( 'The file %s could not be loaded', 'wpdef' ),
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
            $handlers_node = $xmlnodes->item(0);
        } else {
            $handlers_node = $doc->createElement( 'handlers' );
            $xmlnodes      = $xpath->query( '/configuration/system.webServer' );

            if ( $xmlnodes->length > 0 ) {
                $system_webServer_node = $xmlnodes->item(0);
                $handler_fragment      = $doc->createDocumentFragment();
                $handler_fragment->appendXML( $formatxml );
                $system_webServer_node->appendChild( $handler_fragment );
            } else {
                $system_webServer_node = $doc->createElement( 'system.webServer' );
                $handler_fragment      = $doc->createDocumentFragment();
                $handler_fragment->appendXML( $formatxml );
                $system_webServer_node->appendChild( $handler_fragment );
                $xmlnodes = $xpath->query( '/configuration' );

                if ( $xmlnodes->length > 0 ) {
                    $config_node = $xmlnodes->item(0);
                    $config_node->appendChild( $system_webServer_node );
                } else {
                    $config_node = $doc->createElement( 'configuration' );
                    $doc->appendChild( $config_node );
                    $config_node->appendChild( $system_webServer_node );
                }
            }
        }

        $rule_fragment = $doc->createDocumentFragment();
        $rule_fragment->appendXML( $formatxml );
        $handlers_node->appendChild( $rule_fragment );

        $doc->encoding     = 'UTF-8';
        $doc->formatOutput = true;
        saveDomDocument( $doc, $path .'/'. $filename );

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

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        if ( $doc->load( $path . '/' . $filename ) === false ) {
            return false;
        }

        $xpath    = new DOMXPath( $doc );
        $handlers = $xpath->query( '/configuration/system.webServer/handlers[contains(@accessPolicy,\'Read\')]' );

        if ( $handlers->length > 0 ) {
            $child  = $handlers->item(0);
            $parent = $child->parentNode;
            $parent->removeChild( $child );
            $doc->formatOutput = true;
            saveDomDocument( $doc, $path .'/'. $filename );
        }

        return true;
    }

    /**
     * Get the new HT config.
     *
     * @return array - $new_htaccess_config
     */
    public function get_new_htaccess_config() {
        return $this->new_htaccess_config;
    }
}
