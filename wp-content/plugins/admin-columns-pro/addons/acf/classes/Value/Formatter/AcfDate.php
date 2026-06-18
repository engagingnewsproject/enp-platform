<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class AcfDate implements Formatter
{

    public function format(Value $value): Value
    {
        $date_value = (string)$value;

        if (strlen($date_value) === 8 && is_numeric($date_value)) {
            $date = DateTime::createFromFormat('Ymd', $date_value);

            if ( ! $date instanceof DateTime) {
                throw new ValueNotFoundException('Invalid date string. ID ' . $value->get_id());
            }

            return $value->with_value(
                (int)$date->format('U')
            );
        }

        $date = strtotime($date_value);

        if (false === $date) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value((int)$date);
    }

}