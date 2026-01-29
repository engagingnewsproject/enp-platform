<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class Stars implements Formatter
{

    private int $max_stars;

    public function __construct(int $max_stars = 5)
    {
        $this->max_stars = $max_stars;
    }

    public function format(Value $value)
    {
        $stars = ac_helper()->html->stars((int)$value->get_value(), $this->max_stars);

        return $value->with_value($stars);
    }

}