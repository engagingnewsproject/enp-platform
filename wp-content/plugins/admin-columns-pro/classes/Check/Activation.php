<?php

namespace ACP\Check;

use AC\Admin\Page\Addons;
use AC\Admin\Page\Columns;
use AC\Admin\Page\Settings;
use AC\Ajax;
use AC\Capabilities;
use AC\Message;
use AC\Registerable;
use AC\Screen;
use AC\Storage;
use AC\Type\Uri;
use AC\Type\Url;
use ACP\Access\ActivationStorage;
use ACP\Access\Permissions;
use ACP\Access\PermissionsStorage;
use ACP\ActivationTokenFactory;
use ACP\Admin\Page\License;
use ACP\Admin\Page\Tools;
use ACP\AdminColumnsPro;
use ACP\Type\Url\AccountFactory;

class Activation implements Registerable
{

    private AdminColumnsPro $plugin;

    private ActivationTokenFactory $activation_token_factory;

    private ActivationStorage $activation_storage;

    private PermissionsStorage $permission_storage;

    private AccountFactory $account_url_factory;

    public function __construct(
        AdminColumnsPro $plugin,
        ActivationTokenFactory $activation_token_factory,
        ActivationStorage $activation_storage,
        PermissionsStorage $permission_storage,
        AccountFactory $account_url_factory
    ) {
        $this->plugin = $plugin;
        $this->activation_token_factory = $activation_token_factory;
        $this->activation_storage = $activation_storage;
        $this->permission_storage = $permission_storage;
        $this->account_url_factory = $account_url_factory;
    }

    public function register(): void
    {
        add_action('ac/screen', [$this, 'register_notice']);

        $this->get_ajax_handler()->register();
    }

    private function get_ajax_handler(): Ajax\Handler
    {
        $handler = new Ajax\Handler();
        $handler
            ->set_action('ac_notice_dismiss_activation')
            ->set_callback([$this, 'ajax_dismiss_notice']);

        return $handler;
    }

    public function register_notice(Screen $screen): void
    {
        if ( ! $screen->has_screen() || ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        switch (true) {
            case $screen->is_plugin_screen() && $this->show_message() :
                $notice = new Message\Plugin(
                    $this->get_message(),
                    $this->plugin->get_basename(),
                    Message::INFO
                );
                $notice->register();
                break;
            case (
                     $screen->is_admin_screen(Settings::NAME) ||
                     $screen->is_admin_screen(Columns::NAME) ||
                     $screen->is_admin_screen(Tools::NAME) ||
                     $screen->is_admin_screen(Addons::NAME) ||
                     $screen->is_admin_screen(License::NAME)) && $this->show_message() :
                $notice = new Message\AdminNotice($this->get_message());
                $notice
                    ->set_type(Message::INFO)
                    ->register();
                break;
            case $screen->is_table_screen() && $this->get_dismiss_option()->is_expired() && $this->show_message() :

                // Dismissible message on list table
                $notice = new Message\Notice\Dismissible($this->get_message(), $this->get_ajax_handler());
                $notice
                    ->set_type(Message::INFO)
                    ->register();
                break;
        }
    }

    private function show_message(): bool
    {
        // We send a different (locked) message when a user has no usage permissions
        $has_usage = $this->permission_storage->retrieve()->has_permission(Permissions::USAGE);

        if ( ! $has_usage) {
            return false;
        }

        $token = $this->activation_token_factory->create();
        $activation = $token ? $this->activation_storage->find($token) : null;

        if ( ! $activation) {
            return true;
        }

        // An expired license has its own message
        if ($activation->is_expired()) {
            return false;
        }

        return ! $activation->is_active();
    }

    private function get_license_page_url(): Uri
    {
        return $this->plugin->is_network_active()
            ? new Url\EditorNetwork('license')
            : new Url\Editor('license');
    }

    private function get_account_url(): Url\UtmTags
    {
        return new Url\UtmTags($this->account_url_factory->create(), 'license-activation');
    }

    private function get_message(): string
    {
        return sprintf(
            '%s %s',
            sprintf(
                __(
                    "To enable automatic updates for %s, <a href='%s'>enter your license key</a>.",
                    'codepress_admin_columns'
                ),
                'Admin Columns Pro',
                esc_url($this->get_license_page_url()->get_url())
            ),
            sprintf(
                __('You can find your license key on your %s.', 'codepress-admin-columns'),
                sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url($this->get_account_url()->get_url()),
                    __('account page', 'codepress-admin-columns')
                )
            )
        );
    }

    private function get_dismiss_option(): Storage\Timestamp
    {
        return new Storage\Timestamp(
            new Storage\UserMeta('ac_notice_dismiss_activation')
        );
    }

    public function ajax_dismiss_notice(): void
    {
        $this->get_ajax_handler()->verify_request();
        $this->get_dismiss_option()->save(time() + (MONTH_IN_SECONDS * 2));

        exit;
    }

}