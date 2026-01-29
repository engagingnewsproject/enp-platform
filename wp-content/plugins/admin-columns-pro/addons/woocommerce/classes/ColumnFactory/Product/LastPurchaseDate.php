<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class LastPurchaseDate extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private ComponentFactory\DateFormat\Date $date_format;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\DateFormat\Date $date_format
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->date_format = $date_format;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->date_format->create($config));
    }

    public function get_label(): string
    {
        return __('Last Purchase Date', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-last_purchase_date';
    }

    protected function add_formatters(FormatterCollection $formatters, Config $config): void
    {
        $formatters->prepend(new Formatter\Order\Date\CreatedDate());
        $formatters->prepend(new Formatter\Product\LastOrderId());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\LastPurchaseDate();
    }

}