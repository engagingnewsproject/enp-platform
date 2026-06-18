<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter;

use ACP\ConditionalFormat\Formatter;

class PriceFormatter extends Formatter\FloatFormatter
{

    public function format(string $value, $id, string $operator_group): string
    {
        $price = $this->get_price($value);

        return $price
            ? (string)$price
            : '';
    }

    private function get_price($price): ?float
    {
        $price = html_entity_decode($price);
        $price = trim(strip_tags($price));

        $price = $this->remove_currency_symbol($price);
        $price = str_replace([wc_get_price_thousand_separator(), "\xC2\xA0", ','], ["", "", '.'], $price);

        if ( ! is_numeric($price)) {
            return null;
        }

        return (float)$price;
    }

    private function remove_currency_symbol(string $value): string
    {
        $currency_symbol = html_entity_decode(
            get_woocommerce_currency_symbol()
        );

        if (str_starts_with($value, $currency_symbol)) {
            return substr($value, strlen($currency_symbol));
        }

        return $value;
    }

}