<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Sorting\Type\DataType;

class AmountFactory extends OriginalColumnFactory
{

    private const META_KEY = 'coupon_amount';

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopCoupon\Amount();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\ShopCoupon\Amount());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Decimal(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, new DataType(DataType::NUMERIC));
    }

}