<?php

namespace ACP\Asset\Script;

use AC;
use AC\Asset\Location;
use AC\Asset\Script;
use AC\Asset\Script\Localize\Translation;
use AC\Nonce\Ajax;
use AC\Type\Url\EditorNetwork;
use ACP\ActivationTokenFactory;
use ACP\Type\Url\AccountFactory;
use Plugin_Upgrader;

class LicenseManager extends Script
{

    private bool $network_activated;

    private ActivationTokenFactory $token_factory;

    private AccountFactory $url_factory;

    public function __construct(
        Location\Absolute $location,
        bool $network_activated,
        AccountFactory $url_factory
    ) {
        parent::__construct(
            'acp-license-manager',
            $location,
            [
                Script\GlobalTranslationFactory::HANDLE,
            ]
        );

        $this->network_activated = $network_activated;
        $this->url_factory = $url_factory;
    }

    public function register(): void
    {
        parent::register();

        $this->add_inline_variable(
            'acp_license',
            [
                '_ajax_nonce'                   => (new Ajax())->create(),
                'update_nonce'                  => wp_create_nonce('updates'),
                'network_activated'             => $this->network_activated,
                'is_network_admin'              => is_network_admin(),
                'network_license_url'           => (new EditorNetwork('license'))->get_url(),
                'pricing_url'                   => (string)AC\Type\Url\Site::create_pricing(),
                'can_manage_network_capability' => current_user_can('manage_network_options'),
                'manage_license_url'            => (string)$this->url_factory->create(),
            ]
        );

        $translation = Translation::create([
            'license'                      => __('License', 'codepress-admin-columns'),
            'license_description'          => __('License information', 'codepress-admin-columns'),
            'license_key'                  => __('License Key', 'codepress-admin-columns'),
            'license_status'               => __('License Status', 'codepress-admin-columns'),
            'manage_license_label'         => __('Manage License', 'codepress-admin-columns'),
            'activate_license'             => __('Activate License', 'codepress-admin-columns'),
            'deactivate_license'           => __('Deactivate License', 'codepress-admin-columns'),
            'refresh'                      => __('Refresh', 'codepress-admin-columns'),
            'no_license'                   => __("Don't have an Admin Columns Pro license?", 'codepress-admin-columns'),
            'view_pricing'                 => __('View pricing & purchase', 'codepress-admin-columns'),
            'plugin_updates'               => __('Plugin Update', 'codepress-admin-columns'),
            'plugin_updates_description'   => __('Plugin Version and available updates.', 'codepress-admin-columns'),
            'current_version'              => __('Current version', 'codepress-admin-columns'),
            'latest_version'               => __('Latest version', 'codepress-admin-columns'),
            'changelog'                    => __('Changelog', 'codepress-admin-columns'),
            'update_plugin'                => __('Update Plugin', 'codepress-admin-columns'),
            'check_updates'                => __('Check For Updates', 'codepress-admin-columns'),
            'plugin_update_success'        => $this->get_plugin_update_success_string(),
            'license_removal_confirmation' => __(
                'Are you sure you want deactivate Admin Columns Pro?',
                'codepress-admin-columns'
            ),
            'license_removal_explanation'  => __(
                'You need to fill in your license key again if you want to reactivate.',
                'codepress-admin-columns'
            ),

            'updating_plugin'       => __('Updating plugin', 'codepress-admin-columns'),
            'network_page_settings' => __('network settings page', 'codepress-admin-columns'),
            'manage_license'        => __('The license can be managed on the %s.', 'codepress-admin-columns'),
            'enter_license_key_to'  => __('Enter your license key to %s.', 'codepress-admin-columns'),
            'unlock_all_settings'   => __('unlock all settings', 'codepress-admin-columns'),
            'receive_updates'       => __('receive automatic updates', 'codepress-admin-columns'),
            'subscription_expires'  => __('Subscription Expires', 'codepress-admin-columns'),
            'subscription_expired'  => __('Subscription Expired', 'codepress-admin-columns'),
        ]);

        $this->localize('acp_license_i18n', $translation);
    }

    private function get_plugin_update_success_string()
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $upgrader = new Plugin_Upgrader();
        $upgrader->upgrade_strings();

        return $upgrader->strings['process_success'] ?? null;
    }

}