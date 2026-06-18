<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MLA\Export;
use ACP\Column\OriginalColumnFactory;
use MLACore;

class Featured extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        if ( ! MLACore::$process_featured_in) {
            return null;
        }

        return FormatterCollection::from_formatter(new Export\Formatter\FeaturedIn());
    }

}