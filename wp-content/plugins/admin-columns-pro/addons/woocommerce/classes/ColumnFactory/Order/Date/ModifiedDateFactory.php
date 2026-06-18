<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Date;

use AC\Formatter\Date\Timestamp;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\DateFormat;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ModifiedDateFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private DateFormat\Date $date_format;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        DateFormat\Date $date_format
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
        return __('Date Modified', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_date_modified';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new Formatter\Order\Date\ModifiedDate(),
            new Timestamp(),
        ]);

        return $formatters->merge(parent::get_formatters($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\Date\ModifiedDate();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderBy('date_modified');
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new FormatterCollection([
                    new Formatter\Order\Date\ModifiedDate(),
                ]),
                'Y-m-d H:i:s',
            )
        );
    }

}