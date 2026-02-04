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
use ACP\ConditionalFormat\Formatter\SanitizedFormatter;

class TotalWeightFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Total Order Weight', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-order_weight';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Formatter\Order\OrderWeight());
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            SanitizedFormatter::from_ignore_strings(
                new ACP\ConditionalFormat\Formatter\FloatFormatter()
            )
        );
    }

}