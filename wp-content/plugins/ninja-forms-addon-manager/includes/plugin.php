<?php

namespace NinjaFormsAddonManager;

/**
 * The main plugin singleton.
 */
final class Plugin extends WordPress\Plugin
{
    const NINJA_FORMS_MIN_VERSION = '3.3.2';

    public function setup( $version, $file ) {

        $this->version = $version;
        $this->url = plugin_dir_url( $file );
        $this->dir = plugin_dir_path( $file );

        // Setup the service integration for Ninja Forms.
        $this->service = (new Service)->setup();

        /** Actions */
        if( isset( $_REQUEST[ 'nf_webhook' ] ) ){
          add_action( 'plugins_loaded', [ $this, 'route_wehbook' ] );
        }

        /** Filters */
        if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          add_filter( 'http_request_args', [ $this, 'whitelist_request' ], 10, 2 );
        }

        add_action( 'admin_notices', [ $this, 'ninja_forms_min_version' ] );
    }

    /**
     * Route the incoming webhook to the appropriate controller.
     */
    public function route_wehbook() {

        $webhook = $_REQUEST[ 'nf_webhook' ];
        $controllers = $this->config( 'webhooks', 'controllers' );

        // Setup routing to the apropriate controller.
        $controller = new Webhooks\Router( $webhook, $controllers );

        $hash = $_REQUEST[ 'nf_webhook_hash' ];
        $payload = $_REQUEST[ 'nf_webhook_payload' ];
        $client_id = \NinjaForms\OAuth::get_client_id();
        $client_secret = \NinjaForms\OAuth::get_client_secret();

        // Initialize the controller.
        $controller->init( $payload, $hash, $client_id, $client_secret );
    }

    /**
     * Whitelist local requests for development.
     */
    public function whitelist_request( $args, $url ) {
      $args[ 'sslverify' ] = false; // Disbale `sslverify` for local development.
      $args[ 'reject_unsafe_urls' ] = false;
      return $args;
    }

    public function is_ninja_forms_installed() {
      return ( class_exists ( 'Ninja_Forms', $autoload = false ) );
    }

    public static function is_ninja_forms_compatible( $version, $version_required ) {
      return version_compare( $version, $version_required, '>=' );
    }

    public function ninja_forms_min_version() {
      if( ! self::is_ninja_forms_installed() || ! self::is_ninja_forms_compatible( \Ninja_Forms::VERSION, self::NINJA_FORMS_MIN_VERSION ) ){
        echo '<div class="error"><p>'
            . __( 'The Ninja Forms Add-on Manager requires the latest version of Ninja Forms.', 'ninja-forms-addon-manager' )
            . '</p></div>';
      }
    }
}
