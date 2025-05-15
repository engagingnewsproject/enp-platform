<?php

declare(strict_types=1);

namespace ACP\Admin\Page;

use AC\Admin\RenderableHead;
use AC\Asset;
use AC\Asset\Assets;
use AC\Asset\Location;
use AC\Asset\Style;
use AC\Entity\Plugin;
use AC\Renderable;
use AC\Type\Url;
use AC\View;
use ACP;
use ACP\Access\ActivationStorage;
use ACP\Access\PermissionsStorage;
use ACP\ActivationTokenFactory;
use ACP\LicenseKeyRepository;
use ACP\Type\LicenseKey;
use ACP\Type\SiteUrl;
use ACP\Type\Url\Changelog;

class License implements Asset\Enqueueables, Renderable, RenderableHead
{

    public const NAME = 'license';

    private $location;

    private $head;

    private $site_url;

    private $activation_token_factory;

    private $activation_storage;

    private $permission_storage;

    private $license_key_repository;

    private $plugin;

    public function __construct(
        Location\Absolute $location,
        Renderable $head,
        SiteUrl $site_url,
        ActivationTokenFactory $activation_token_factory,
        ActivationStorage $activation_storage,
        PermissionsStorage $permission_storage,
        LicenseKeyRepository $license_key_repository,
        Plugin $plugin
    ) {
        $this->location = $location;
        $this->head = $head;
        $this->site_url = $site_url;
        $this->activation_token_factory = $activation_token_factory;
        $this->activation_storage = $activation_storage;
        $this->permission_storage = $permission_storage;
        $this->license_key_repository = $license_key_repository;
        $this->plugin = $plugin;
    }

    public function render_head(): Renderable
    {
        return $this->head;
    }

    public function get_assets(): Assets
    {
        return new Assets([
            new Style('acp-license-manager', $this->location->with_suffix('assets/core/css/license-manager.css')),
            new ACP\Asset\Script\LicenseManager($this->location->with_suffix('assets/core/js/license-manager.js')),
        ]);
    }

    private function get_changelog_url(string $plugin_name): Changelog
    {
        return new Changelog($this->plugin->is_network_active(), $plugin_name);
    }

    private function show_render_section_updates(): bool
    {
        // update section is hidden on subsites
        return ! is_multisite() || (is_network_admin() && $this->plugin->is_network_active());
    }

    public function render(): string
    {
        if ( ! is_network_admin() && $this->plugin->is_network_active()) {
            return (string)$this->render_network_message();
        }

        $view = new View([
            'section_license' => $this->render_license_section(),
            'section_updates' => $this->show_render_section_updates() ? $this->render_section_updates() : '',
        ]);

        return $view->set_template('admin/page/license')
                    ->render();
    }

    private function render_section_updates(): View
    {
        $content = '';

        $updates_available = false;
        $updates_available_with_package = false;

        $plugin_update = $this->plugin->get_update();

        $content .= $this->render_section_update()->render();

        if ($plugin_update) {
            $updates_available = true;

            if ($plugin_update->has_package()) {
                $updates_available_with_package = true;
            }
        }

        $has_token = null !== $this->activation_token_factory->create();
        $has_update_permission = $this->permission_storage->retrieve()->has_updates_permission();
        $show_update_now_button = $has_token && $updates_available_with_package && $has_update_permission;

        $view = new View([
            'title'                      => __('Updates', 'codepress-admin-columns'),
            'content'                    => $content,
            'button_update_now'          => $show_update_now_button,
            'button_update_now_disabled' => ! $show_update_now_button && $updates_available,
            'button_check_for_updates'   => ! $updates_available,
        ]);

        return $view->set_template('admin/section-updates');
    }

    private function render_section_update(): View
    {
        $plugin_update = $this->plugin->get_update();
        $update_ready = $plugin_update && $plugin_update->has_package() && current_user_can('update_plugins');

        $view = new View([
            'plugin_update_basename' => $this->plugin->get_basename(),
            'plugin_update_slug'     => $this->plugin->get_dirname(),
            'plugin_update_nonce'    => wp_create_nonce('updates'),
            'plugin_update_ready'    => $update_ready,
            'plugin_label'           => $this->plugin->get_name(),
            'current_version'        => $this->plugin->get_version()->get_value(),
            'available_version'      => $plugin_update ? $plugin_update->get_version()->get_value() : null,
            'changelog_link'         => $this->get_changelog_url($this->plugin->get_dirname())->get_url(),
        ]);

        return $view->set_template('admin/section-update');
    }

    private function get_inline_notice(): ?string
    {
        $permissions = $this->permission_storage->retrieve();

        $description = null;

        if ( ! $permissions->has_usage_permission()) {
            $description = __('Enter your license code to receive automatic updates.', 'codepress-admin-columns');
        }

        if ( ! $permissions->has_updates_permission()) {
            $description = sprintf(
                __('Enter your license key to %s.', 'codepress-admin-columns'),
                sprintf(
                    '<strong>%s</strong>',
                    __('unlock all settings', 'codepress-admin-columns')
                )
            );
        }

        return $description
            ? sprintf(
                '%s %s',
                ac_helper()->icon->dashicon(['icon' => 'info-outline', 'class' => 'orange']),
                $description
            )
            : null;
    }

    private function render_network_message(): View
    {
        $page = __('network settings page', 'codepress-admin-columns');

        if (current_user_can('manage_network_options')) {
            $url = new Url\EditorNetwork('license');

            $page = sprintf('<a href="%s">%s</a>', $url->get_url(), $page);
        }

        $content = sprintf(
            __('The license can be managed on the %s.', 'codepress-admin-columns'),
            $page
        );

        $inline_notice = $this->get_inline_notice();

        if ($inline_notice) {
            $content = sprintf('%s %s', $inline_notice, $content);
        }

        $view = new View([
            'title'   => __('License', 'codepress-admin-columns'),
            'content' => sprintf('<p>%s</p>', $content),
            'class'   => '-license',
        ]);

        return $view->set_template('admin/page/settings-section');
    }

    private function render_license_section(): View
    {
        $account_url = new Url\UtmTags(new Url\Site(Url\Site::PAGE_ACCOUNT_SUBSCRIPTIONS), 'license-activation');

        $token = $this->activation_token_factory->create();

        if ($token) {
            $account_url = $account_url->with_arg($token->get_type(), $token->get_token())
                                       ->with_arg('site_url', $this->site_url->get_url());
        }

        $activation_token = $this->activation_token_factory->create();
        $activation = $activation_token
            ? $this->activation_storage->find($activation_token)
            : null;

        $permissions = $this->permission_storage->retrieve();
        $is_expired = $activation && $activation->is_expired();

        // Give auto-renewal 2 extra days before marked as expired
        if ($is_expired && $activation->is_auto_renewal() && $activation->get_expiry_date()->get_expired_seconds(
            ) < (2 * DAY_IN_SECONDS)) {
            $is_expired = false;
        }

        $updates_enabled = ! $is_expired &&
                           $activation &&
                           $activation->is_active() &&
                           $permissions->has_updates_permission();

        $license_key = $this->license_key_repository->find();

        $license_info = new View([
            'nonce_field'                     => (new ACP\Nonce\LicenseNonce())->create_field(),
            'updates_disabled'                => ! $updates_enabled,
            'updates_enabled'                 => $updates_enabled,
            'is_expired'                      => $is_expired,
            'expiry_date'                     => $activation && $activation->get_expiry_date()->exists()
                ? ac_format_date('F j, Y', $activation->get_expiry_date()->get_value()->getTimestamp())
                : false,
            'is_cancelled'                    => $activation && $activation->is_cancelled(),
            'is_active'                       => $activation && $activation->is_active(),
            'is_license_defined'              => $license_key && LicenseKey::SOURCE_CODE === $license_key->get_source(),
            'license_key'                     => $license_key ? $license_key->get_token() : false,
            'has_activation'                  => null !== $activation,
            'has_usage_permission'            => $permissions->has_usage_permission(),
            'my_account_link'                 => $account_url->get_url(),
            'subscription_documentation_link' => (new Url\Documentation(
                Url\Documentation::ARTICLE_SUBSCRIPTION_QUESTIONS
            )),
        ]);

        $view = new View([
            'title'   => __('License', 'codepress-admin-columns'),
            'content' => $license_info->set_template('admin/section-license'),
            'class'   => '-license',
        ]);

        return $view->set_template('admin/page/settings-section');
    }

}