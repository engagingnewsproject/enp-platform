<?php

declare(strict_types=1);

namespace ACP\Formatter\MsUser;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Sites implements Formatter
{

    public function format(Value $value)
    {
        $sites = get_blogs_of_user($value->get_id(), true);

        if (empty($sites)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode(', ', wp_list_pluck($sites, 'siteurl')));
    }

}