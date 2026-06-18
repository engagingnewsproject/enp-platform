<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\DateFormatter;

use AC\Expression\DateOperators;
use ACP\ConditionalFormat\Formatter\DateFormatter;
use DateTime;

class FormatFormatter extends DateFormatter
{

    private ?string $format;

    public function __construct(?string $format = null)
    {
        parent::__construct();

        $this->format = $format;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        $value = parent::format($value, $id, $operator_group);

        if ($operator_group === DateOperators::class) {
            $format = $this->format;
            $raw_value = strip_tags($value);

            if ( ! $raw_value) {
                return $value;
            }

            if ( ! $this->format) {
                $format = 'U';
                $raw_value = (string)strtotime($raw_value);
            }

            $date = DateTime::createFromFormat($format, $raw_value);

            if ($date) {
                $value = $date->format('Y-m-d');
            }
        }

        return $value;
    }

}