<?php

declare(strict_types=1);

namespace ACP\Value\ExtendedValue\NetworkSites;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;
use WP_Site;

class Plugins implements ExtendedValue
{

    private const NAME = 'post-plugins';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        $link = new ExtendedValueLink($label, $id, self::NAME);

        if (current_user_can('activate_plugins')) {
            $link = $link->with_edit_link(admin_url('plugins.php'));
        }

        $site = get_site($id);

        if ($site instanceof WP_Site) {
            $link = $link->with_title(sprintf('%s &ndash; %s', $site->blogname, $site->siteurl));
        }

        return $link->with_class('-w-large');
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $plugins = $this->get_plugin_items($id);

        if (empty($plugins)) {
            return __('No plugins found', 'codepress-admin-columns');
        }

        $view = new View([
            'title'  => is_multisite()
                ? (string)get_blog_option($id, 'blogname')
                : get_bloginfo('name'),
            'amount' => count($plugins),
            'items'  => $plugins,
        ]);

        return $view->set_template('modal-value/plugins')->render();
    }

    private function get_plugin_items(int $id): array
    {
        // Site plugins
        $active_plugins = maybe_unserialize(ac_helper()->network->get_site_option($id, 'active_plugins')) ?: [];

        // Network plugins
        $network_plugins = get_site_option('active_sitewide_plugins') ?: [];

        $active_plugins = array_unique(array_merge($active_plugins, array_keys($network_plugins)));

        $updates = get_plugin_updates();

        $plugins = get_plugins();

        $items = [];

        foreach ($active_plugins as $plugin_file) {
            $plugin_data = $plugins[$plugin_file] ?? [];

            if ( ! $plugin_data) {
                continue;
            }

            $is_network_active = (bool)($network_plugins[$plugin_file] ?? false);

            $update = $updates[$plugin_file]->update->new_version ?? null;

            $items[] = [
                'name'    => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'status'  => $is_network_active
                    ? __('Network activated', 'codepress-admin-columns')
                    : __('Activated', 'codepress-admin-columns'),
                'update'  => $update
                    ? sprintf(__('Update to %s', 'codepress-admin-columns'), $update)
                    : '&ndash;',
            ];
        }

        usort($items, function (array $a, array $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }
}
