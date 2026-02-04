<?php

declare(strict_types=1);

namespace ACA\GravityForms\ColumnFactory\Entry;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\GravityForms\Export;
use ACA\GravityForms\Search;
use ACP;

class SourceUrlFactory extends ACP\Column\OriginalColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Entry\TextColumn('source_url');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Export\Formatter\Entry\EntryProperty('source_url'));
    }

}