<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Formatter\HumanTimeDifference;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class FulfillmentTimeFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Fulfillment Time', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_fulfillment_time';
    }

    public function get_description(): ?string
    {
        return __('Time between order creation and completion.', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
            ->add(new Formatter\Order\FulfillmentTime())
            ->add(new HumanTimeDifference())
        ;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\FulfillmentTime();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\FulfillmentTime());
    }

}
