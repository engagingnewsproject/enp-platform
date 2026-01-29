<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter\ProductVariation;

use AC\Expression\ComparisonOperators;
use ACP\ConditionalFormat\Formatter;
use WC_Product_Variation;

class PriceFormatter extends Formatter\FloatFormatter
{

    public function format(string $value, $id, string $operator_group): string
    {
        if (ComparisonOperators::class === $operator_group) {
            $value = (new WC_Product_Variation($id))->get_price();
        }

        return trim(strip_tags(html_entity_decode($value)));
    }

}