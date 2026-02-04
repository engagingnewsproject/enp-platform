<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class PluginCollection implements Formatter
{

    private bool $include_active_for_network;

    public function __construct(bool $include_active_for_network)
    {
        $this->include_active_for_network = $include_active_for_network;
    }

    public function format(Value $value): ValueCollection
    {
        $active_plugins = new ValueCollection($value->get_id(), []);

        $site_plugins = maybe_unserialize(
            ac_helper()->network->get_site_option((int)$value->get_id(), 'active_plugins')
        );

        foreach (get_plugins() as $basename => $plugin) {
            if ($this->include_active_for_network && is_plugin_active_for_network($basename)) {
                $active_plugins->add(new Value($basename, $plugin));
                continue;
            }

            if (in_array($basename, $site_plugins)) {
                $active_plugins->add(new Value($basename, $plugin));
            }
        }

        return $active_plugins;
    }
}