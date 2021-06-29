<?php

namespace NinjaFormsAddonManager\Webhooks;

class Router
{
    protected $controller;

    public function __construct( $webhook, $controllers = array() )
    {
        if( ! isset( $controllers[ $webhook ] ) ) return;
        $this->controller = new $controllers[ $webhook ];
    }

    public function init( $payload, $hash, $client_id, $client_secret )
    {
        $response = new Response();

        // Check for a `client_id` and verify the hash.
        if( ! $client_id || $hash != sha1( $payload . $client_id . $client_secret  ) ) {
            $response->respond( array(
                'error' => 'Forbidden'
            ), 403 );
        }

        $payload = json_decode( $payload, true );

        $this->controller->process( $payload, $response );
    }
}
