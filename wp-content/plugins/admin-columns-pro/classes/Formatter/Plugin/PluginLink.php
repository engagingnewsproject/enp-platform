<?php

declare(strict_types=1);

namespace ACP\Formatter\Plugin;

use AC\Formatter;
use AC\Type\Value;

class PluginLink implements Formatter
{

    public function format(Value $value)
    {
        if (current_user_can('activate_plugins')) {
            return $value->with_value(
                ac_helper()->html->link(
                    get_admin_url(get_current_blog_id(), 'plugins.php') . '?s=' . $value->get_value(),
                    $value->get_value()
                )
            );
        }

        return $value;
    }

}