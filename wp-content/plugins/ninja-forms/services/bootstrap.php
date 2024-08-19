<?php

namespace NinjaForms;

if( ! defined( 'NF_SERVER_URL' ) )
  define( 'NF_SERVER_URL', 'https://my.ninjaforms.com' );

// Setup OAuth as a prerequisite for services.
include_once plugin_dir_path( __FILE__ ) . 'oauth.php';
OAuth::set_base_url( NF_SERVER_URL . '/oauth' );
OAuth::getInstance()->setup();

add_action( 'wp_ajax_nf_services', function(){
  $services = Ninja_Forms()->config( 'DashboardServices' );
  wp_die( json_encode( [ 'data' => array_values( $services ) ] ) );
});

add_action( 'admin_enqueue_scripts', function() {
  wp_localize_script( 'nf-dashboard', 'nfPromotions', array() );
});

add_action( 'wp_ajax_nf_services_install', function() {

  // register_shutdown_function(function(){
  //   if( ! error_get_last() ) return;
  //   echo '<pre>';
  //   print_r( error_get_last() );
  //   echo '</pre>';
  // });

  if ( ! current_user_can('install_plugins') )
    die( json_encode( [ 'error' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'ninja-forms' ) ] ) );

  if ( ! isset($_REQUEST['security']) || empty($_REQUEST['security']) || ! wp_verify_nonce($_REQUEST['security'], 'ninja_forms_dashboard_nonce') )
    die( json_encode( [ 'error' => esc_html__( 'Invalid nonce.', 'ninja-forms' ) ] ) );

  $plugin = \WPN_Helper::sanitize_text_field($_REQUEST['plugin']);
  $install_path = \WPN_Helper::sanitize_text_field($_REQUEST['install_path']);

  // If we aren't remotely installing the add-on manager or SendWP, die.
  if ( 'sendwp' !== $plugin && 'ninja-forms-addon-manager' !== $plugin )
    die( json_encode( [ 'error' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'ninja-forms' ) ] ) );

  include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
  $api = plugins_api( 'plugin_information', array(
    'slug' => $plugin,
    'fields' => array(
      'short_description' => false,
      'sections' => false,
      'requires' => false,
      'rating' => false,
      'ratings' => false,
      'downloaded' => false,
      'last_updated' => false,
      'added' => false,
      'tags' => false,
      'compatibility' => false,
      'homepage' => false,
      'donate_link' => false,
    ),
  ) );

  if ( is_wp_error( $api ) ) {
    die( json_encode( [ 'error' => $api->get_error_message() ] ) );
  }

  $plugins = get_plugins();
  if( ! isset( $plugins[ $install_path ] ) ){
    if ( ! class_exists( 'Plugin_Upgrader' ) ) {
      include_once ABSPATH . 'wp-admin/includes/file.php';
      include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }

    include_once plugin_dir_path( __FILE__ ) . 'remote-installer-skin.php';
    ob_start();
    $upgrader = new \Plugin_Upgrader( new Remote_Installer_Skin() );
    $install = $upgrader->install( $api->download_link );
    ob_clean();

    if( ! $install ){
      die( json_encode( [ 'error' => $upgrader->skin->get_errors() ] ) );
    }
  }

  if( ! is_plugin_active($plugin) ){
    ob_start();
    $activated = activate_plugin( $install_path );
    ob_clean();
    if( is_wp_error( $activated ) ){
      die( json_encode( [ 'error' => $activated->get_error_message() ] ) );
    }
  }

  $response = apply_filters( 'nf_services_installed_' . $plugin, '1' );

  echo json_encode( $response );
  die( '1' );
});

/**
 * Override the Ninja Mail download link until published in the repository.
 */
/*
add_filter( 'plugins_api_result', function( $response, $action, $args ){
  if( 'plugin_information' !== $action ) return $response;
  if( 'ninja-mail' !== $args->slug ) return $response;

  $response = new \stdClass();
  $response->download_link = 'http://my.ninjaforms.com/wp-content/uploads/ninja-mail-792d39446223d14b8464e214773e7786627855d8.zip';

  return $response;
}, 10, 3 );
*/
/**
 * Override the Add-on Manager download link until published in the repository.
 */
/*
add_filter( 'plugins_api_result', function( $response, $action, $args ){
  if( 'plugin_information' !== $action ) return $response;
  if( 'ninja-forms-addon-manager' !== $args->slug ) return $response;

  $response = new \stdClass();
  $response->download_link = 'http://my.ninjaforms.com/wp-content/uploads/ninja-forms-addon-manager-4b6a3f724b27d6d9f7d4e89ebe12dad215ec1b20.zip';

  return $response;
}, 10, 3 );

add_filter( 'http_request_args', function( $args, $url ){
  if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    $args['sslverify'] = false; // Local development
    $args['reject_unsafe_urls'] = false;
  }
  return $args;
}, 10, 2 );
*/
add_action( 'wp_ajax_nf_update_cache_mode', function() {
  $use_cache = false;
  $response = array();

  check_ajax_referer( 'ninja_forms_dashboard_nonce', 'security' );

  if( ! current_user_can('manage_options') ) {
    $response[ 'errors' ] = array( "Current user doesn't have permission." );

    echo json_encode( $response );
    die();
  }

  

  if(!isset( $_POST[ 'cache_mode' ] ) ) {
    $response[ 'errors' ] = array( 'No cache mode value given' );

    echo json_encode( $response );
    die();
  }

  $use_cache = ( intval($_POST[ 'cache_mode' ]) === 1 ) ? true : false;

  update_option( 'ninja_forms_cache_mode', $use_cache );

  $response['message'] = 'Cache mode successfully saved';

  echo json_encode($response);
  die();
});
