<?php

declare(strict_types=1);

namespace ACA\GravityForms\ColumnFactory\Entry;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\GravityForms\Export;
use ACA\GravityForms\Search;
use ACP;

class DatePaymentFactory extends ACP\Column\OriginalColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Entry\DateColumn('payment_date');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Export\Formatter\Entry\EntryProperty('payment_date'));
    }

}