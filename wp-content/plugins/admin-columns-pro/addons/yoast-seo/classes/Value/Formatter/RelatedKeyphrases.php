<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Type\Value;

class RelatedKeyphrases implements AC\Formatter
{

    public function format(Value $value): Value
    {
        $raw = json_decode($value->get_value());
        $values = [];

        if (empty($raw)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }
        foreach ($raw as $keyphrase) {
            $values[] = sprintf('<strong>%s</strong>: %s', $keyphrase->keyword, $keyphrase->score);
        }

        return $value->with_value(implode('<br>', $values));
    }

}