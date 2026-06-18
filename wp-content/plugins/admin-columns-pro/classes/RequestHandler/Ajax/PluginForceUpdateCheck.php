<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce\Ajax;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\ActivationTokenFactory;
use ACP\Updates\PluginDataUpdater;

class PluginForceUpdateCheck implements RequestAjaxHandler
{

    private PluginDataUpdater $products_updater;

    private ActivationTokenFactory $token_factory;

    public function __construct(PluginDataUpdater $products_updater, ActivationTokenFactory $token_factory)
    {
        $this->products_updater = $products_updater;
        $this->token_factory = $token_factory;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! (new Ajax())->verify($request)) {
            return;
        }

        $this->products_updater->update($this->token_factory->create());

        wp_clean_plugins_cache();
        wp_update_plugins();
        wp_send_json_success();
    }

}