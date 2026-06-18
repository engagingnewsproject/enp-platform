<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class WcPrice implements Formatter
{

    private bool $skip_zero;

    private ?string $currency;

    public function __construct(bool $skip_zero = true, ?string $currency = null)
    {
        $this->skip_zero = $skip_zero;
        $this->currency = $currency;
    }

    public function format(Value $value)
    {
        $price = $value->get_value();

        if ( ! is_numeric($price)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ($this->skip_zero && ! $price) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $args = [];

        if ($this->currency) {
            $args['currency'] = $this->currency;
        }

        return $value->with_value(
            wc_price($price, $args)
        );
    }

}