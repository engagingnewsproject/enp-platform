<?php

declare(strict_types=1);

namespace ACA\WC\Helper;

use WC_Tax;

final class Tax
{

    public function get_tax_class_options(): array
    {
        $classes = [];

        foreach (WC_Tax::get_tax_classes() as $tax_class) {
            $classes[WC_Tax::format_tax_rate_class($tax_class)] = $tax_class;
        }

        return $classes;
    }

}