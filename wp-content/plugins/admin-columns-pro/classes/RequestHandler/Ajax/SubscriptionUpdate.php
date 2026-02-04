<?php

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\Nonce;
use AC\RequestAjaxHandler;
use ACP\Access\ActivationUpdater;
use ACP\ActivationTokenFactory;
use ACP\Transient\TimeTransientFactory;

class SubscriptionUpdate implements RequestAjaxHandler
{

    private ActivationTokenFactory $token_factory;

    private Nonce\Ajax $nonce;

    private ActivationUpdater $activation_updater;

    public function __construct(
        ActivationTokenFactory $token_factory,
        Nonce\Ajax $nonce,
        ActivationUpdater $activation_updater
    ) {
        $this->token_factory = $token_factory;
        $this->nonce = $nonce;
        $this->activation_updater = $activation_updater;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new AC\Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $activation_token = $this->token_factory->create();

        if ( ! $activation_token) {
            wp_send_json_error();
        }

        $transient = TimeTransientFactory::create_license_check_daily();

        if ( ! $transient->is_expired()) {
            return;
        }

        $transient->save();

        $api_response = $this->activation_updater->update($activation_token);

        if ($api_response->has_error()) {
            wp_send_json_error($api_response->get_error()->get_error_message());
        }

        wp_send_json_success();
    }

}