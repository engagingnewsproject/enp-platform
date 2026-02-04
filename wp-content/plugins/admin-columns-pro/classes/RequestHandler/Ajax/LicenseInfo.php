<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\Url;
use ACP\Access\ActivationStorage;
use ACP\Access\PermissionsStorage;
use ACP\ActivationTokenFactory;
use ACP\Type\SiteUrl;

class LicenseInfo implements RequestAjaxHandler
{

    private Nonce\Ajax $nonce;

    private ActivationStorage $activation_storage;

    private ActivationTokenFactory $activation_token_factory;

    private PermissionsStorage $permissions_storage;

    private SiteUrl $site_url;

    public function __construct(
        ActivationTokenFactory $activation_token_factory,
        ActivationStorage $activation_storage,
        PermissionsStorage $permissions_storage,
        Nonce\Ajax $nonce,
        SiteUrl $site_url
    ) {
        $this->nonce = $nonce;
        $this->activation_storage = $activation_storage;
        $this->activation_token_factory = $activation_token_factory;
        $this->permissions_storage = $permissions_storage;
        $this->site_url = $site_url;
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

        $activation_token = $this->activation_token_factory->create();
        $activation = $activation_token
            ? $this->activation_storage->find($activation_token)
            : null;

        if ( ! $activation) {
            wp_send_json_error();
        }

        $account_url = new Url\UtmTags(
            Url\Site::create_account(),
            'license-activation'
        );
        $account_url = $account_url->with_arg('site_url', (string)$this->site_url);
        $account_url = $account_url->with_arg(
            $activation_token->get_type(),
            $activation_token->get_token()
        );

        $status = $activation->get_status()->get_value();
        $is_expired = $activation->is_expired();

        // Give auto-renewal 2 extra days before marked as expired
        if ($is_expired
            && $activation->is_auto_renewal()
            && $activation->has_expiry_date()
            && $activation->get_expiry_date()->get_expired_seconds() < (2 * DAY_IN_SECONDS)) {
            $status = 'active';
        }

        $updates_enabled = ! $is_expired &&
                           $activation->is_active() &&
                           $this->permissions_storage->retrieve()->has_updates_permission();

        $data = [
            'method'          => $activation->get_renewal_method()->get_value(),
            'status'          => $status,
            'account_url'     => $account_url->get_url(),
            'updates_enabled' => $updates_enabled,
            'lifetime'        => $activation->is_lifetime(),
            'expiration_date' => $activation->has_expiry_date() ?
                ac_format_date('F j, Y', $activation->get_expiry_date()->get_value()->getTimestamp())
                : null,
        ];

        wp_send_json_success($data);
    }

}