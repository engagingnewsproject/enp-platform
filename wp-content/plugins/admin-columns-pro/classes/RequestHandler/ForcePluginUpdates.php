<?php

namespace ACP\RequestHandler;

use AC\Capabilities;
use AC\Form\Nonce;
use AC\Request;
use ACP\ActivationTokenFactory;
use ACP\RequestHandler;
use ACP\Updates\PluginDataUpdater;

class ForcePluginUpdates implements RequestHandler
{

    /**
     * @var PluginDataUpdater
     */
    private $products_updater;

    /**
     * @var ActivationTokenFactory
     */
    private $token_factory;

    public function __construct(PluginDataUpdater $products_updater, ActivationTokenFactory $token_factory)
    {
        $this->products_updater = $products_updater;
        $this->token_factory = $token_factory;
    }

    public function handle(Request $request): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        if ( ! (new Nonce('acp-force-plugin-update', '_acnonce'))->verify($request)) {
            return;
        }

        $this->products_updater->update($this->token_factory->create());

        wp_clean_plugins_cache();
        wp_update_plugins();
    }

}