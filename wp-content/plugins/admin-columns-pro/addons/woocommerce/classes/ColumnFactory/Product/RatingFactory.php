<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\Aggregate;
use AC\Formatter\Composite;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter\Product\AverageRating;
use ACA\WC\Value\Formatter\Product\LinkedRatingCount;
use ACA\WC\Value\Formatter\Stars;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\FormatCollectionFormatter;

class RatingFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_wc_average_rating';

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_rating';
    }

    public function get_label(): string
    {
        return __('Average Rating', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $stars = Aggregate::from_array([
            new AverageRating(),
            new Stars(),
        ]);

        $rating = new LinkedRatingCount();

        return new FormatterCollection([
            new Composite([
                $stars,
                $rating,
            ]),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta(self::META_KEY));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Rating();
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            FormatCollectionFormatter::create(
                [
                    new AverageRating(),
                ],
                ACP\ConditionalFormat\Formatter::FLOAT
            )
        );
    }

}