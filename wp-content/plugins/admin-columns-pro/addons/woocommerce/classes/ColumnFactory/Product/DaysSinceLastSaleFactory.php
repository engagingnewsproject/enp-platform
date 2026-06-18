<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\HumanTimeDifference;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DaysSinceLastSaleFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Days Since Last Sale', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-days_since_last_sale';
    }

    public function get_description(): ?string
    {
        return __('Time since this product was last purchased.', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
            ->add(new Formatter\Product\DaysSinceLastSale())
            ->add(new HumanTimeDifference())
        ;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\DaysSinceLastSale();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\DaysSinceLastSale());
    }

}
