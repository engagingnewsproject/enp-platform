<?php

namespace NinjaFormsAddonManager\Webhooks;

use NinjaFormsAddonManager\Plugin;

final class Sync implements Controller
{
    public $payload = '';
    public $response = '';

    public function process( $payload, $response )
    {
      $this->payload = $payload;
      $this->response = $response;
      add_action( 'init', array( $this, 'get_plugins' ), 10 );
    }

    public function get_plugins()
    {
      $plugins = get_plugins();
      $active_plugins = get_option( 'active_plugins' );

      $this->response->respond( [
        'plugins' => array_keys( $plugins ),
        'active' => array_values( $active_plugins )
      ] );
    }
}
