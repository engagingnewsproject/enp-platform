<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing\Product\ReviewsEnabled;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class ReviewsEnabledFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-reviews_enabled';
    }

    public function get_label(): string
    {
        return __('Reviews Enabled', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Product\ReviewsEnabled());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ReviewsEnabled();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Property('comment_status'));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\PostField('comment_status');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\ReviewsEnabled();
    }

}