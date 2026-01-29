<?php

declare(strict_types=1);

namespace ACA\WC\Helper\Price;

class Rounding
{

    public function up(float $price, int $decimals = 0): float
    {
        $decimals = rtrim((string)$decimals, '0');

        if ( ! $decimals) {
            $decimals = 0;
        }

        $digits = strlen((string)$decimals);
        $divider = pow(10, $digits);

        $rounding = absint($decimals);
        $fraction = absint($divider * ($price - floor($price)));

        if ($fraction < $rounding) {
            return floor($price) + ($rounding / $divider);
        }

        return floor($price) + 1 + ($rounding / $divider);
    }

    public function down(float $price, int $decimals = 0): float
    {
        if ($this->price_digits_are_same($price, $decimals)) {
            return $price;
        }

        $decimals = rtrim((string)$decimals, '0');
        $digits = strlen($decimals);
        $divider = pow(10, $digits);

        $rounding = absint($decimals);
        $fraction = absint($divider * ($price - floor($price)));

        if ($fraction >= $rounding) {
            return floor($price) + ($rounding / $divider);
        }

        return floor($price) - 1 + ($rounding / $divider);
    }

    private function price_digits_are_same(float $price, int $decimals): bool
    {
        $price_digits = explode('.', (string)$price);
        $price_decimals = rtrim($price_digits[1], '0');
        $decimals = rtrim((string)$decimals, '0');

        return $price_decimals === $decimals;
    }

}