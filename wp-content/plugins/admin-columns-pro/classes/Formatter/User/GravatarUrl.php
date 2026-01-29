<?php

declare(strict_types=1);

namespace ACP\Formatter\User;

use AC;
use AC\Type\Value;

class GravatarUrl implements AC\Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(get_avatar_url($value->get_id()));
    }

}