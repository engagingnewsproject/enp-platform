<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MLA\Export;
use ACP\Column\OriginalColumnFactory;

class CustomField extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Export\Formatter\CustomField($this->get_column_type()));
    }

}