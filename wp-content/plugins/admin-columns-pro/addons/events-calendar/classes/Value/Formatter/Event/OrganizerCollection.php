<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class OrganizerCollection implements AC\Formatter
{

    public function format(Value $value): AC\Type\ValueCollection
    {
        $values = array_filter(get_post_meta($value->get_id(), '_EventOrganizerID'));

        if (empty($values)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return AC\Type\ValueCollection::from_ids($value->get_id(), $values);
    }
}