<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class FeaturedIn implements Formatter
{

    use ExtendedPostTrait;
    use FormatPostStatusTrait;
    use UnshiftArrayTrait;

    public function format(Value $value)
    {
        $item = $this->get_extended_post((int)$value->get_id());

        if ($item === null) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $features = $item->mla_references['features'] ?? [];
        $features = $this->shift_element_to_top($features, $item->post_parent);

        $values = [];

        foreach ($features as $feature) {
            $parent = $feature->ID === $item->post_parent
                ? sprintf(", %s", __('PARENT', 'media-library-assistant'))
                : '';

            $values[] = sprintf(
                "%1\$s (%2\$s %3\$s%4\$s%5\$s)",
                esc_attr($feature->post_title),
                esc_attr($feature->post_type),
                $feature->ID,
                $this->format_post_status($feature->post_status),
                $parent
            );
        }

        return $value->with_value(implode(",\n", $values));
    }

}