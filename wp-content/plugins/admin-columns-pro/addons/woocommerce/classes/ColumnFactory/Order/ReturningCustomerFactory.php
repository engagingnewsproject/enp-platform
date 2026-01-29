<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Formatter\YesNoIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class ReturningCustomerFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Returning Customer', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_returning_customer';
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\ReturningCustomer();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\Stats(
            'returning_customer',
            new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\IsReturningCustomer())
                     ->add(new YesNoIcon());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\IsReturningCustomer());
    }

}