<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\Config;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\FloatFormatter;
use ACP\ConditionalFormat\Formatter\SanitizedFormatter;

class CurrencyFactory extends NumberFactory
{

    public function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(SanitizedFormatter::from_ignore_strings(new FloatFormatter()));
    }

}