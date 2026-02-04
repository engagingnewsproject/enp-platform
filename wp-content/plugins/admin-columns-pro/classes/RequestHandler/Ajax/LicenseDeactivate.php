<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce\Ajax;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Access\ActivationKeyStorage;
use ACP\Access\ActivationStorage;
use ACP\Access\PermissionChecker;
use ACP\Access\Rule\ApiDeactivateResponse;
use ACP\ActivationTokenFactory;
use ACP\API;
use ACP\ApiFactory;
use ACP\LicenseKeyRepository;
use ACP\Type\SiteUrl;
use ACP\Updates\PluginDataUpdater;

class LicenseDeactivate implements RequestAjaxHandler
{

    private LicenseKeyRepository $license_key_repository;

    private ActivationKeyStorage $activation_key_storage;

    private ActivationStorage $activation_storage;

    private ApiFactory $api_factory;

    private SiteUrl $site_url;

    private ActivationTokenFactory $activation_token_factory;

    private PluginDataUpdater $products_updater;

    private PermissionChecker $permission_checker;

    public function __construct(
        LicenseKeyRepository $license_key_repository,
        ActivationKeyStorage $activation_key_storage,
        ActivationStorage $activation_storage,
        ApiFactory $api,
        SiteUrl $site_url,
        ActivationTokenFactory $activation_token_factory,
        PluginDataUpdater $products_updater,
        PermissionChecker $permission_checker
    ) {
        $this->license_key_repository = $license_key_repository;
        $this->activation_key_storage = $activation_key_storage;
        $this->activation_storage = $activation_storage;
        $this->api_factory = $api;
        $this->site_url = $site_url;
        $this->activation_token_factory = $activation_token_factory;
        $this->products_updater = $products_updater;
        $this->permission_checker = $permission_checker;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! (new Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $token = $this->activation_token_factory->create();

        $this->license_key_repository->delete();
        $this->activation_key_storage->delete();
        $this->activation_storage->delete();
        $this->permission_checker->apply();

        if ( ! $token) {
            return;
        }

        $response = $this->api_factory->create()->dispatch(
            new API\Request\Deactivate($token, $this->site_url)
        );

        $this->permission_checker
            ->add_rule(new ApiDeactivateResponse($response))
            ->apply();

        if ($response->has_error()) {
            wp_send_json_error($response->get_error()->get_error_message());
        }

        $this->products_updater->update($token);

        wp_clean_plugins_cache();
        wp_update_plugins();

        wp_send_json_success($response->get('message'));
    }

}