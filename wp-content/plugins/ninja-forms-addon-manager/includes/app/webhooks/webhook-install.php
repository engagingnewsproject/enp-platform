<?php

namespace NinjaFormsAddonManager\Webhooks;

use NinjaFormsAddonManager\Plugin;

final class Install implements Controller
{
    public $payload = '';
    public $response = '';

    public function process( $payload, $response )
    {
        $this->payload = $payload;
        $this->response = $response;
        add_action( 'init', array($this, 'init'), 10);
    }

    public function init()
    {
      if( ! isset( $this->payload[ 'download' ] ) || ! isset( $this->payload[ 'license' ] ) ) {
        $this->response->respond( array(
          'error' => 'Resource Not Found'
        ), 404 );
      };

      $this->set_auth_cookie();

      $file_name = urldecode( $this->payload[ 'file_name' ] );
      if( $file_path = $this->is_plugin_installed( $file_name ) ){
        $this->activate_plugin( $file_path );
        $this->activate_license( $this->payload[ 'slug' ], $this->payload[ 'license' ] );
        $this->response->respond( array( 'message' => 'Plugin already installed' ) );
      }

      $result = $this->_install_plugin( $this->payload[ 'download' ], $this->payload[ 'license' ], NF_SERVER_URL );

      if( is_wp_error( $result ) ){

        $this->response->respond(array(
          'error' => 'Plugin installer error.',
          'debug' => serialize( $result ),
        ), 500 );

      } elseif( ! isset( $result[ 'destination_name' ] ) ){

        /** DEBUGGING */
        $api_args = array_merge( array(
          'item_name'  => urlencode( $this->payload[ 'download' ] ),
          'license'    => $this->payload[ 'license' ],
        ), Plugin::config( 'edd', 'download_link' ) );
        $download_link = add_query_arg( $api_args, NF_SERVER_URL );
        /** END DEBUGGING */

        $this->response->respond(array(
          'error' => 'Plugin failed to install.',
          'debug' => $result,
          'download_link' => $download_link,
          'file_name' => $this->payload[ 'file_name' ]
        ), 500 );
      }


      // Confirm that the plugin was installed.
      $file_path = $this->is_plugin_installed( $result[ 'destination_name' ] );
      if( ! $file_path ){
        $this->response->respond(array(
          'error' => 'Could not confirm that the plugin was installed.'
        ), 500 );
      }

      $this->activate_plugin( $file_path );

      $this->activate_license( $this->payload[ 'slug' ], $this->payload[ 'license' ] );

      $this->clear_auth_cookie();

      // @todo Check for errors.
      $this->response->respond( 'success' );
    }

    /**
    * Literally installs the plugin
    *
    * @since 3.0.12 Updated download URL/Link to use EDD SL endpoint.
    *
    * @param string $download Download slug.
    * @param string $license Valid license key.
    * @param string $api_url Download URL.
    *
    * @return bool
    */
    private function _install_plugin( $download, $license, $api_url )
    {
      $download_url = add_query_arg( array(
        'edd_action' => 'get_version',
        'item_name' => $download,
        'license' => $license,
        'url' => site_url(),
      ), 'https://ninjaforms.com/' );

      $response = wp_remote_get( $download_url );
      $response_body = wp_remote_retrieve_body( $response );
      $response_body_parsed = json_decode( $response_body );

      $download_link = $response_body_parsed->download_link;

        if ( ! class_exists( 'Plugin_Upgrader' ) ) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        require_once( Plugin::dir( 'lib/wordpress/remote-installer-skin.php' ) );

        $skin = new \NinjaFormsAddonManager\WordPress\Remote_Installer_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );

        $install = $upgrader->install( $download_link );

        if( is_wp_error( $install ) ){
          return $install;
        }

        if( ! $install ){

          return $upgrader->skin->get_errors();
        }

        return $upgrader->result;
    }

    /**
     * Check if a plugin is installed (by file name).
     *
     * @param string $file_name
     * @return string|bool The file path of the installed plugin or false if not.
     */
    private function is_plugin_installed( $file_name ) {

      if ( ! function_exists( 'get_plugins' ) ) {
          require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      foreach( get_plugins() as $file_path => $plugin ){
          $path = explode( '/', $file_path );
          // Check folder name as well as full file path.
          if( $file_name !== $path[0] && $file_path !== $file_name ) continue;
          return $file_path;
      }
      return false;
    }

    /**
     * Activate a plugin based on the file path.
     *
     * @param string $file_path;
     */
    private function activate_plugin( $file_path ) {
      $activated = activate_plugin( $file_path );
      if ( is_wp_error( $activated ) ) {
          // Process Error
          $this->response->respond(array(
              'error' => 'Plugin did not activate.'
          ), '404' ); // @todo Update error code.
      }
    }

    /**
     * Activate the add-on license locally.
     *
     * @param string $slug
     * @param string $license
     */
    private function activate_license( $slug, $license ) {
      Ninja_Forms()->update_setting( $slug . '_license', $license );
      Ninja_Forms()->update_setting( $slug . '_license_error', '' ); // Manually clear errors.
      Ninja_Forms()->update_setting( $slug . '_license_status', 'valid' ); // Manually set status.
    }

    /**
     * Set auth cookies for permission to create files on the server.
     * This will be cleared after the request.
     */
    private function set_auth_cookie() {
      $users = get_users();
      $user = reset( $users );
      wp_set_auth_cookie( $user->ID );
    }

    /**
     * Clear any auth cookies that we set earlier.
     */
    private function clear_auth_cookie() {
      wp_clear_auth_cookie();
    }
}
