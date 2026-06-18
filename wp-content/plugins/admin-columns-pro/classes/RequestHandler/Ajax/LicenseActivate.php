<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Access\ActivationKeyStorage;
use ACP\Access\ActivationUpdater;
use ACP\Access\PermissionCheckerFactory;
use ACP\Access\Rule\ApiErrorResponse;
use ACP\Access\Rule\ApiPermissionResponse;
use ACP\API;
use ACP\ApiFactory;
use ACP\Type\Activation\Key;
use ACP\Type\LicenseKey;
use ACP\Type\SiteUrl;
use ACP\Updates\PluginDataUpdater;
use InvalidArgumentException;

class LicenseActivate implements RequestAjaxHandler
{

    private ActivationKeyStorage $activation_key_storage;

    private ApiFactory $api_factory;

    private SiteUrl $site_url;

    private PluginDataUpdater $plugins_updater;

    private ActivationUpdater $activation_updater;

    private Nonce\Ajax $nonce;

    private PermissionCheckerFactory $permission_factory;

    public function __construct(
        ActivationKeyStorage $activation_key_storage,
        ApiFactory $api_factory,
        SiteUrl $site_url,
        PluginDataUpdater $plugins_updater,
        ActivationUpdater $activation_updater,
        PermissionCheckerFactory $permission_factory,
        Nonce\Ajax $nonce
    ) {
        $this->activation_key_storage = $activation_key_storage;
        $this->api_factory = $api_factory;
        $this->site_url = $site_url;
        $this->plugins_updater = $plugins_updater;
        $this->activation_updater = $activation_updater;
        $this->nonce = $nonce;
        $this->permission_factory = $permission_factory;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error(__('Invalid request', 'codepress-admin-columns'));
        }

        $key = sanitize_text_field($request->get('license'));

        if ( ! $key) {
            wp_send_json_error(__('Empty license.', 'codepress-admin-columns'));
        }

        if ( ! LicenseKey::is_valid($key)) {
            wp_send_json_error(__('Invalid license.', 'codepress-admin-columns'));
        }

        $api_request = new API\Request\Activate(
            new LicenseKey($key),
            $this->site_url
        );

        $response = $this->api_factory->create()->dispatch($api_request);

        // set permissions based on API response
        $this->permission_factory
            ->create()
            ->add_rule(new ApiPermissionResponse($response))
            ->add_rule(new ApiErrorResponse($response))
            ->apply();

        if ($response->has_error()) {
            wp_send_json_error($response->get_error()->get_error_message());
        }

        try {
            $activation_key = new Key((string)$response->get('activation_key'));
        } catch (InvalidArgumentException $e) {
            wp_send_json_error('Invalid activation key received from API. Please try again. If the issue persists, contact support.');
        }

        $this->activation_key_storage->save($activation_key);
        $this->activation_updater->update($activation_key);
        $this->plugins_updater->update($activation_key);

        wp_clean_plugins_cache();
        wp_update_plugins();

        wp_send_json_success($response->get('message'));
    }

}