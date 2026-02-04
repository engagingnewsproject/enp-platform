<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class DescriptionFactory extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopCoupon\Description();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\ShopCoupon\Description());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\PostField('post_excerpt');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Excerpt();
    }

}