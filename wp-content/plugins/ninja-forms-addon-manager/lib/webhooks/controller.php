<?php

namespace NinjaFormsAddonManager\Webhooks;

interface Controller
{
    public function process( $payload, $response );
}
