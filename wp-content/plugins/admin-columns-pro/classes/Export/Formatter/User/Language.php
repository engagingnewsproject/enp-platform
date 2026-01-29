<?php

declare(strict_types=1);

namespace ACP\Export\Formatter\User;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Language implements Formatter
{

    public function format(Value $value): Value
    {
        $translations = (new AC\Helper\Translations())->get_available_translations();
        $locale = $value->get_value();

        $label = $translations[$locale]['native_name'] ?? null;

        if ( ! $label) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($label);
    }

}