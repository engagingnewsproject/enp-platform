<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Sorting\Type\DataType;

class IsCustomerFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Is Customer', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_is_customer';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Order\IsCustomer())
                     ->add(new Formatter\Order\IsCustomerIcon());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\IsCustomer());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderData('customer_id', DataType::create_numeric());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\IsCustomer();
    }

}