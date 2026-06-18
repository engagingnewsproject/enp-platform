<?php

declare(strict_types=1);

namespace ACP\Formatter;

use AC;
use AC\Helper;
use AC\Type\Value;

class LanguageNativeName implements AC\Formatter
{

    public function format(Value $value)
    {
        $translations = Helper\Translations::create()->get_available_translations();
        $locale = $value->get_value();

        return $value->with_value(
            $translations[$locale]['native_name'] ??
            Helper\Html::create()->tooltip(
                '&ndash;',
                _x('Site Default', 'default site language')
            )
        );
    }

}