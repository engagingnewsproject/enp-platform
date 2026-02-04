<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP\Column\AdvancedColumnFactory;

class Orders extends AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-coupon_orders';
    }

    public function get_label(): string
    {
        return __('Orders', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Value\Formatter\ShopCoupon\Orders())
                     ->add(
                         new Value\Formatter\Product\ExtendedValueLink(
                             new Value\ExtendedValue\ShopCoupon\Orders(),
                             ''
                         )
                     );
    }

}