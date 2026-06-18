<?php

declare(strict_types=1);

namespace ACP\Formatter\Plugin;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class PluginName implements Formatter
{

    public function format(Value $value)
    {
        $plugin_data = $value->get_value();

        if ( ! isset($plugin_data['Name'])) {
            throw new ValueNotFoundException(sprintf('Plugin %s not found', $value->get_id()));
        }

        return $value->with_value($plugin_data['Name']);
    }

}