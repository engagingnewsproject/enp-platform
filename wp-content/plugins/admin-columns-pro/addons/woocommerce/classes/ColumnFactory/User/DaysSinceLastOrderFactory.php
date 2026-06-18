<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\Formatter\HumanTimeDifference;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DaysSinceLastOrderFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Days Since Last Order', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-days_since_last_order';
    }

    public function get_description(): ?string
    {
        return __("Number of days since this customer's most recent order.", 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
            ->add(new Formatter\User\DaysSinceLastOrder())
            ->add(new HumanTimeDifference())
        ;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\DaysSinceLastOrder();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\User\DaysSinceLastOrder());
    }

}
