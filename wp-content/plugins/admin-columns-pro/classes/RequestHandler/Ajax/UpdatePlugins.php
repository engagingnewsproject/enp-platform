<?php

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Nonce;
use AC\RequestAjaxHandler;
use ACP\ActivationTokenFactory;
use ACP\Transient\TimeTransientFactory;
use ACP\Updates\PluginDataUpdater;

class UpdatePlugins implements RequestAjaxHandler
{

    private ActivationTokenFactory $activation_token_factory;

    private PluginDataUpdater $updater;

    public function __construct(
        ActivationTokenFactory $activation_token_factory,
        PluginDataUpdater $updater
    ) {
        $this->activation_token_factory = $activation_token_factory;
        $this->updater = $updater;
    }

    public function handle(): void
    {
        $request = new AC\Request();

        if ( ! (new Nonce\Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $cache = TimeTransientFactory::create_update_check();

        if ($cache->is_expired()) {
            $this->updater->update($this->activation_token_factory->create());

            $cache->save();
        }
    }

}