<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Organizer\Link;

use AC\Formatter;
use AC\Type\Value;

class WebsiteLink implements Formatter
{

    public function format(Value $value)
    {
        $email = get_post_meta($value->get_id(), '_OrganizerWebsite', true);

        return $email ? $value->with_value(sprintf('<a href="%s">%s</a>', $email, $value)) : $value;
    }

}