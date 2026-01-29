<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\OriginalColumnFactory;

class ExpiryDate extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\ShopCoupon\ExpiryDate());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopCoupon\ExpiryDate();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\ShopCoupon\ExpiryDate('date_expires');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
            'date_expires',
            (new AC\Meta\QueryMetaFactory())->create_with_post_type('date_expires', 'shop_coupon')
        );
    }

}