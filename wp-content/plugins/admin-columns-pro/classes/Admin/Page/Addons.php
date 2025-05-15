<?php

namespace ACP\Admin\Page;

use AC;
use AC\Asset\Assets;
use AC\Asset\Location;
use AC\IntegrationRepository;
use AC\Renderable;
use AC\View;
use ACP\Settings\General\IntegrationStatus;

class Addons extends AC\Admin\Page\Addons
{

    private $integration_status;

    public function __construct(
        IntegrationStatus $integration_status,
        Location\Absolute $location,
        IntegrationRepository $integrations,
        Renderable $head
    ) {
        parent::__construct($location, $integrations, $head);
        $this->integration_status = $integration_status;
    }

    public function get_assets(): Assets
    {
        return parent::get_assets()->add(
            new AC\Asset\Script('acp-tools', $this->location->with_suffix('../assets/core/js/admin-page-addons.js'))
        );
    }

    protected function render_actions(AC\Integration $addon): ?Renderable
    {
        if ( ! $addon->is_plugin_active()) {
            return null;
        }

        return (new View([
            'integration' => $addon->get_slug(),
            'status'      => $this->integration_status->is_active($addon->get_slug()),
        ]))->set_template('admin/page/component/addon-action');
    }

    protected function get_grouped_addons(): array
    {
        $groups = [];

        $active = $this->integrations->find_all_by_active_plugins();

        if ($active->exists()) {
            $groups[] = [
                'title'        => __('Active', 'codepress-admin-columns'),
                'class'        => 'active',
                'integrations' => $active,
            ];
        }

        $not_active = $this->integrations->find_all_by_inactive_plugins();

        if ($not_active->exists()) {
            $groups[] = [
                'title'        => __('Available', 'codepress-admin-columns'),
                'class'        => 'available',
                'integrations' => $not_active,
            ];
        }

        return $groups;
    }

}