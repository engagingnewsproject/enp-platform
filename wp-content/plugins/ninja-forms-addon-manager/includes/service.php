<?php

namespace NinjaFormsAddonManager;

/**
 * The Ninja Forms "Service" registration.
 */
class Service
{
  const SLUG = 'ninja-forms-addon-manager';
  protected $base_url;

  public function __construct() {
    // ...
  }

  public function setup() {
    add_filter( 'ninja_forms_services', [ $this, 'register_service' ] );
    add_filter( 'ninja-forms-dashboard-promotions', [ $this, 'remove_promotion' ] );
    return $this;
  }

  public function register_service( $services ){

    $services[ self::SLUG ] = [
      'name' => __( 'Add-on Manager (Beta)', 'ninja-forms-addon-manager' ),
      'slug' => self::SLUG,
      'description' => 'Install any purchased Ninja Forms add-ons with a single click. No need to ever download a zip file or copy paste a license key! <strong>* Requires a live server.</strong>',
      'enabled' => true,
      'serviceLink' => [
        'text' => 'Manage Add-ons',
        'href' => trailingslashit( NF_SERVER_URL ) . 'addon-manager/',
        'classes' => '',
        'target' => '_blank',
      ],
    ];

    return $services;
  }

  public function remove_promotion( $promotions ){
    unset( $promotions[ self::SLUG ] );
    return $promotions;
  }
}
