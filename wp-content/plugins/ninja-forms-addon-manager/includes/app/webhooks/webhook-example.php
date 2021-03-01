<?php

namespace NinjaFormsAddonManager\Webhooks;

final class Example implements Controller
{
    public function process( $payload, $response )
    {
        $response->respond( $payload );
    }
}
