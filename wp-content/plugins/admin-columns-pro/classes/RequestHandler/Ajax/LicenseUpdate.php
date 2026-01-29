<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce\Ajax;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Access\ActivationUpdater;
use ACP\ActivationTokenFactory;

class LicenseUpdate implements RequestAjaxHandler
{

    private $activation_token_factory;

    private $activation_updater;

    public function __construct(ActivationTokenFactory $activation_token_factory, ActivationUpdater $activation_updater)
    {
        $this->activation_token_factory = $activation_token_factory;
        $this->activation_updater = $activation_updater;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            wp_send_json_error();
        }

        $request = new Request();

        if ( ! (new Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $token = $this->activation_token_factory->create();

        if ( ! $token) {
            wp_send_json_error('Missing activation token.');
        }

        $api_response = $this->activation_updater->update($token);

        if ($api_response->has_error()) {
            wp_send_json_error($api_response->get_error()->get_error_message());
        }

        wp_send_json_success(__('License information has been updated.', 'codepress-admin-columns'));
    }

}