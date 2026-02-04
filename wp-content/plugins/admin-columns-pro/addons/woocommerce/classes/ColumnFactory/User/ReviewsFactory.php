<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class ReviewsFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Reviews', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-reviews';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\User\ReviewCount());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\User\CommentCount(
            [ACP\Sorting\Model\User\CommentCount::STATUS_APPROVED],
            ['product']
        );
    }

}