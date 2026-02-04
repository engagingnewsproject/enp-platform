<?php

declare(strict_types=1);

namespace ACP\Formatter;

use AC;
use AC\Type\Value;

class LanguageNativeName implements AC\Formatter
{

    public function format(Value $value)
    {
        $translations = (new AC\Helper\Translations())->get_available_translations();
        $locale = $value->get_value();

        return $value->with_value(
            $translations[$locale]['native_name'] ??
            ac_helper()->html->tooltip(
                '&ndash;',
                _x('Site Default', 'default site language')
            )
        );
    }

}