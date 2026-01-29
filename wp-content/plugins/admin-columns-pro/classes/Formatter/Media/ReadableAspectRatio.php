<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Type\Value;

class ReadableAspectRatio implements AC\Formatter
{

    public function format(Value $value)
    {
        $ratios = [
            '0.25' => '1:4',
            '0.33' => '1:3',
            '0.5'  => '1:2',
            '0.56' => '9:16',
            '0.6'  => '3:5',
            '0.67' => '2:3',
            '0.75' => '3:4',
            '0.80' => '4:5',
            '1'    => '1:1',
            '1.25' => '5:4',
            '1.33' => '4:3',
            '1.5'  => '3:2',
            '1.66' => '5:3',
            '1.6'  => '16:10',
            '1.78' => '16:9',
        ];

        $ratio = array_key_exists($value->get_value(), $ratios)
            ? $ratios[$value->get_value()]
            : $value->get_value() . ':1';

        return $value->with_value($ratio);
    }

}