<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\Formatter\Date\Timestamp;
use AC\Formatter\NullFormatter;
use AC\FormatterCollection;
use AC\Meta\QueryMetaFactory;
use AC\Setting;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\DateType;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting\Type\DataType;

class OrderDate extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private DateType $date_type;

    private Setting\ComponentFactory\DateFormat\Date $date_format;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        DateType $date_type,
        Setting\ComponentFactory\DateFormat\Date $date_format
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->date_type = $date_type;
        $this->date_format = $date_format;
    }

    public function get_column_type(): string
    {
        return 'column-wc-order_date';
    }

    public function get_label(): string
    {
        return __('Order Date', 'codepress-admin-columns');
    }

    private function get_date_type($config): string
    {
        return $config->get(DateType::NAME, 'completed');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->date_type->create($config))
                     ->add($this->date_format->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection();

        switch ($this->get_date_type($config)) {
            case 'created':
                $formatters->add(new Formatter\Order\Date\CreatedDate());
                break;
            case 'completed':
                $formatters->add(new Formatter\Order\Date\CompletedDate());
                break;
            case 'modified':
                $formatters->add(new Formatter\Order\Date\ModifiedDate());
                break;
            case 'paid':
                $formatters->add(new Formatter\Order\Date\PaidDate());
                break;
            default:
                $formatters->add(new NullFormatter());
        }
        $formatters->add(new Timestamp());

        return $formatters->merge(parent::get_formatters($config));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($this->get_date_type($config)) {
            case 'created':
                return new ACP\Sorting\Model\Post\PostField('post_date', new DataType(DataType::DATETIME));
            case'completed':
                return new ACP\Sorting\Model\Post\Meta('_date_completed', new DataType(DataType::NUMERIC));
            case 'modified':
                return new ACP\Sorting\Model\Post\PostField('post_modified', new DataType(DataType::DATETIME));
            case 'paid':
                return new ACP\Sorting\Model\Post\Meta('_date_paid', new DataType(DataType::NUMERIC));
            default:
                return null;
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($this->get_date_type($config)) {
            case 'created':
                return new ACP\Search\Comparison\Post\Date\PostDate('shop_order');
            case 'completed':
                return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
                    '_date_completed',
                    (new QueryMetaFactory())->create_with_post_type('_date_completed', 'shop_order')
                );
            case 'modified':
                return new ACP\Search\Comparison\Post\Date\PostModified('shop_order');

            case 'paid':
                return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
                    '_date_paid',
                    (new QueryMetaFactory())->create_with_post_type('_date_paid', 'shop_order')
                );
            default:
                return null;
        }
    }

}