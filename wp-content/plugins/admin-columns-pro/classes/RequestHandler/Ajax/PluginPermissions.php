<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Access\PermissionsStorage;

class PluginPermissions implements RequestAjaxHandler
{

    private PermissionsStorage $permissions_storage;

    private Nonce\Ajax $nonce;

    public function __construct(
        PermissionsStorage $permissions_storage,
        Nonce\Ajax $nonce
    ) {
        $this->permissions_storage = $permissions_storage;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        wp_send_json_error([
            'permissions' => $this->permissions_storage->retrieve()->to_array(),
        ]);
    }

}