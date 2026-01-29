<?php

namespace ACP\ConditionalFormat;

use AC\Setting\Config;
use ACP\ConditionalFormat\Formatter\FilterHtmlFormatter;
use ACP\ConditionalFormat\Formatter\StringFormatter;

trait FilteredHtmlFormatTrait
{

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            new FilterHtmlFormatter(new StringFormatter())
        );
    }

}