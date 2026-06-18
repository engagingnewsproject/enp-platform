<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Organizer\Link;

use AC\Formatter;
use AC\Type\Value;

class EmailLink implements Formatter
{

    public function format(Value $value)
    {
        $email = get_post_meta((int)$value->get_id(), '_OrganizerEmail', true);

        return $email
            ? $value->with_value(
                sprintf('<a href="mailto:%s">%s</a>', $email, $value)
            )
            : $value;
    }

}