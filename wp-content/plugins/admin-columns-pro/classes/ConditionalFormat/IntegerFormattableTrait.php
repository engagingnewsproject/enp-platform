<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Setting\Config;
use ACP\ConditionalFormat\Formatter\IntegerFormatter;

trait IntegerFormattableTrait
{

    public function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(new IntegerFormatter());
    }

}