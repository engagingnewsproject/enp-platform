<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon;

use AC\Formatter\Post\ExcerptRaw;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\WordLimit;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Description extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private WordLimit $word_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        WordLimit $word_limit
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->word_limit = $word_limit;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->word_limit->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-coupon_description';
    }

    public function get_label(): string
    {
        return __('Description', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new ExcerptRaw());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ShopCoupon\Description();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Value\Formatter\ShopCoupon\Description());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Excerpt();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\PostField('post_excerpt');
    }

}