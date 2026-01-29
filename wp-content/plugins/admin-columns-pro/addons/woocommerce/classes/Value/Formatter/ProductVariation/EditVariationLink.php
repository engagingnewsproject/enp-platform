<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Formatter;
use AC\Type\Value;

class EditVariationLink implements Formatter
{

    public function format(Value $value)
    {
        $link = get_edit_post_link($value->get_id()) . '#variation_' . $value->get_id();

        $label = $value->get_value();

        if (str_starts_with($label, '<a href')) {
            $label = strip_tags($label);
        }

        return $value->with_value(
            ac_helper()->html->link($link, $label)
        );
    }

}