<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP\Column\OriginalColumnFactory;

class CouponFactory extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\ShopCoupon\CouponCode());
    }

}