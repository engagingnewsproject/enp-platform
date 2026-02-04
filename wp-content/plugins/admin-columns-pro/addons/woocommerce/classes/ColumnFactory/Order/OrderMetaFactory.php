<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Formatter\Date\Timestamp;
use AC\Formatter\Date\WordPressDateFormat;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\DateSaveFormat;
use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\MetaField;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\Formatter\FilterHtmlFormatter;
use ACP\ConditionalFormat\Formatter\StringFormatter;
use ACP\Sorting\Type\DataType;

class OrderMetaFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private MetaField $meta_field;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        MetaField $meta_field
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->meta_field = $meta_field;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->meta_field->create($config));
    }

    public function get_label(): string
    {
        return __('Order Meta', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_meta';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend($this->get_base_formatter($config));
    }

    private function get_base_formatter($config): \AC\Formatter
    {
        return new Formatter\Order\OrderMeta($config->get('meta_field', ''));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return (new Editing\Order\OrderMetaFactory())->create($config);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new Search\Order\OrderMetaFactory())->create($config);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $meta_key = $config->get('meta_field', '');
        $field_type = $config->get('field_type', '');

        switch ($field_type) {
            case FieldType::TYPE_POST :
            case FieldType::TYPE_USER :
            case FieldType::TYPE_COUNT :
                return null;
            case FieldType::TYPE_NUMERIC :
                return new Sorting\Order\OrderMeta($meta_key, new DataType(DataType::NUMERIC));
            case FieldType::TYPE_DATE :
                $date_format = $config->get('date_save_format', '');

                return $date_format === DateSaveFormat::FORMAT_UNIX_TIMESTAMP
                    ? new Sorting\Order\OrderMeta($meta_key, new DataType(DataType::NUMERIC))
                    : new Sorting\Order\OrderMeta($meta_key, new DataType(DataType::DATE));

            default :
                return new Sorting\Order\OrderMeta($meta_key);
        }
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        switch ($config->get('field_type', '')) {
            case FieldType::TYPE_IMAGE :
            case FieldType::TYPE_MEDIA :
                return new FormatterCollection([
                    $this->get_base_formatter($config),
                    new Formatter\Order\Meta\MediaUrl(),
                ]);

            case FieldType::TYPE_DATE :
                return FormatterCollection::from_formatter($this->get_base_formatter($config));
            default:
                return parent::get_export($config);
        }
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        $field_type = $config->get('field_type', '');

        switch ($field_type) {
            case FieldType::TYPE_NON_EMPTY :
            case FieldType::TYPE_BOOLEAN :
            case FieldType::TYPE_MEDIA :
            case FieldType::TYPE_COLOR :
            case FieldType::TYPE_IMAGE :
                return null;
            case FieldType::TYPE_DATE :
                return new ConditionalFormat\FormattableConfig(
                    new ConditionalFormat\Formatter\DateFormatter\DateValueFormatter(
                        new FormatterCollection([
                            $this->get_base_formatter($config),
                            new Timestamp(),
                            new WordPressDateFormat('Y-m-d', 'U'),
                        ])
                    )
                );

            case FieldType::TYPE_COUNT :
            case FieldType::TYPE_NUMERIC :
                return new ConditionalFormat\FormattableConfig(
                    new ConditionalFormat\Formatter\IntegerFormatter()
                );
            case FieldType::TYPE_POST :
            case FieldType::TYPE_USER :
                return new ConditionalFormat\FormattableConfig(
                    new FilterHtmlFormatter(
                        new StringFormatter()
                    )
                );
            default:
                return new ConditionalFormat\FormattableConfig();
        }
    }
}