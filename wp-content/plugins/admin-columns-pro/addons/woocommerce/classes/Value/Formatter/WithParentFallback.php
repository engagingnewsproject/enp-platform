<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Formatter;
use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\Type\Value;
use WC_Product_Variation;

class WithParentFallback implements Formatter
{

    private Aggregate $aggregate;

    public function __construct(FormatterCollection $formatters)
    {
        $this->aggregate = new Aggregate($formatters);
    }

    public function format(Value $value): Value
    {
        $result = $this->aggregate->format($value);

        if ('' !== (string)$result) {
            return $result;
        }

        $product = wc_get_product((int)$value->get_id());

        if ( ! $product instanceof WC_Product_Variation) {
            return $result;
        }

        $parent_result = $this->aggregate->format(new Value($product->get_parent_id()));

        return $value->with_value($parent_result->get_value());
    }

}
