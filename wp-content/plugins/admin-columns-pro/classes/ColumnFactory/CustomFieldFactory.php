<?php

declare(strict_types=1);

namespace ACP\ColumnFactory;

use AC;
use AC\Column\Context;
use AC\Column\CustomFieldContext;
use AC\FormatterCollection;
use AC\Meta;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\ComponentFactory\DateSaveFormat;
use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACP;
use ACP\ApplyFilter\CustomField\StoredDateFormat;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\IntegerFormatter;
use ACP\Editing;
use ACP\Editing\Service;
use ACP\Editing\Service\ServiceFactory;
use ACP\Search;
use ACP\Sorting;
use ACP\Sorting\Type\DataType;

class CustomFieldFactory extends ACP\Column\AdvancedColumnFactory
{

    private ComponentFactory\FieldType $field_type_factory;

    private ComponentFactory\BeforeAfter $before_after_factory;

    private ComponentFactory\CustomFieldFactory $custom_field_factory;

    private Editing\Setting\ComponentFactory\InlineEditContentTypeFactory $inline_edit_content_type_factory;

    private Sorting\Model\MetaFactory $sorting_factory;

    private ComponentFactory\IsMultiple $is_multiple_factory;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date;

    private TableScreenContext $table_context;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TableScreenContext $table_context,
        ComponentFactory\CustomFieldFactory $custom_field_factory,
        ComponentFactory\FieldType $field_type_factory,
        ComponentFactory\IsMultiple $is_multiple_factory,
        ComponentFactory\BeforeAfter $before_after_factory,
        Editing\Setting\ComponentFactory\InlineEditContentTypeFactory $inline_edit_content_type_factory,
        Sorting\Model\MetaFactory $sorting_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->custom_field_factory = $custom_field_factory;
        $this->field_type_factory = $field_type_factory;
        $this->before_after_factory = $before_after_factory;
        $this->inline_edit_content_type_factory = $inline_edit_content_type_factory;
        $this->sorting_factory = $sorting_factory;
        $this->is_multiple_factory = $is_multiple_factory;
        $this->filtering_date = $filtering_date;
        $this->table_context = $table_context;
    }

    public function get_column_type(): string
    {
        return 'column-meta';
    }

    public function get_label(): string
    {
        return __('Custom Field', 'codepress-admin-columns');
    }

    private function get_meta_key(Config $config): string
    {
        return (string)$config->get('field', '');
    }

    private function get_field_type(Config $config): string
    {
        return (string)$config->get('field_type', '');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $components = [
            $this->custom_field_factory->create($this->table_context)->create($config),
            $this->field_type_factory->create($config),
        ];

        if ($this->get_field_type($config) === FieldType::TYPE_POST) {
            $components[] = $this->is_multiple_factory->create($config);
        }

        $components[] = $this->before_after_factory->create($config);

        return new ComponentCollection($components);
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);

        $edit_setting = $this->get_field_type($config) === FieldType::TYPE_TEXT
            ? $this->inline_edit_content_type_factory->create(new Editing\Setting\ComponentFactory\EditableType\Text())
            : (new Editing\Setting\ComponentFactory\InlineEditCustomField());

        $builder->set_edit($edit_setting);

        if ($this->get_field_type($config) === FieldType::TYPE_DATE) {
            $builder->set_search(null, $this->filtering_date);
        }

        return $builder;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        if ($this->get_field_type($config) === FieldType::TYPE_COUNT) {
            return $formatters->prepend(
                AC\Formatter\Aggregate::from_array([
                    new AC\Formatter\MetaCollection($this->get_meta_type(), $this->get_meta_key($config)),
                    new AC\Formatter\Count(),
                ])
            );
        }

        return $formatters->prepend(
            $this->get_meta_formatter($config)
        );
    }

    protected function get_editing(Config $config): ?Service
    {
        $storage = new Editing\Storage\Meta(
            $this->get_meta_key($config),
            $this->get_meta_type()
        );

        switch ($this->get_field_type($config)) {
            case FieldType::TYPE_HTML :
                return ServiceFactory::create_wysiwyg($storage);
            case FieldType::TYPE_ARRAY :
                return ServiceFactory::create_serialized($storage, (array)$config->get('array_keys'));
            case FieldType::TYPE_BOOLEAN:
                return ServiceFactory::create_toggle($storage);
            case FieldType::TYPE_COLOR:
                return ServiceFactory::create_color($storage);
            case FieldType::TYPE_DATE:
                $date_format = $this->get_date_save_format($config);

                switch ($date_format) {
                    case DateSaveFormat::FORMAT_UNIX_TIMESTAMP:
                    case DateSaveFormat::FORMAT_DATETIME:
                        return ServiceFactory::create_date_time($storage, $date_format);
                    default:
                        return ServiceFactory::create_date($storage, $date_format);
                }
            case FieldType::TYPE_IMAGE:
                return ServiceFactory::create_image($storage);
            case FieldType::TYPE_MEDIA:
                return ServiceFactory::create_media($storage);
            case FieldType::TYPE_SELECT :
                return 'on' === $config->get('is_multiple', 'off')
                    ? ServiceFactory::create_multiple_select($storage, $this->get_select_options($config))
                    : ServiceFactory::create_select($storage, $this->get_select_options($config));

            case FieldType::TYPE_URL:
                return ServiceFactory::create_internal_link($storage);
            case FieldType::TYPE_NUMERIC:
                return ServiceFactory::create_number($storage);
            case FieldType::TYPE_POST:
                $post_types = (array)apply_filters(
                    'ac/editing/custom_field/post_types',
                    [],
                    $config,
                    $this->table_context
                );

                return 'on' === $config->get('is_multiple', 'on')
                    ? ServiceFactory::create_posts($storage, $post_types)
                    : ServiceFactory::create_post($storage, $post_types);
            case FieldType::TYPE_USER:
                return ServiceFactory::create_users($storage);
            case FieldType::TYPE_COUNT:
            case FieldType::TYPE_NON_EMPTY:
                return null;
            default :
                $type = $config->get('editable_type') ?: 'textarea';
                switch ($type) {
                    case Editing\Setting\ComponentFactory\EditableType\Content::TYPE_WYSIWYG:
                        return ServiceFactory::create_wysiwyg($storage);
                    case Editing\Setting\ComponentFactory\EditableType\Content::TYPE_TEXTAREA:
                        return ServiceFactory::create_textarea($storage);
                    default:
                        return ServiceFactory::create_text($storage);
                }
        }
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        switch ((string)$this->get_field_type($config)) {
            case FieldType::TYPE_ARRAY:
                return null;
            case FieldType::TYPE_BOOLEAN:
                return $this->sorting_factory->create(
                    $this->get_meta_type(),
                    $this->get_meta_key($config),
                    DataType::create_numeric()
                );
            case FieldType::TYPE_NUMERIC:
                $numeric_type = apply_filters('ac/sorting/custom_field/numeric_type', DataType::NUMERIC, $config);

                return $this->sorting_factory->create(
                    $this->get_meta_type(),
                    $this->get_meta_key($config),
                    new DataType($numeric_type)
                );
            case FieldType::TYPE_DATE:
                switch ($this->get_date_save_format($config)) {
                    case DateSaveFormat::FORMAT_DATE :
                        $data_type = DataType::create_date();
                        break;
                    case DateSaveFormat::FORMAT_UNIX_TIMESTAMP :
                        $data_type = DataType::create_numeric();
                        break;
                    default :
                        $data_type = DataType::create_date_time();
                }

                // $date_type can be `string`, `numeric`, `date` or `datetime`
                $data_type = apply_filters('ac/sorting/custom_field/date_type', $data_type, $config);

                return $this->sorting_factory->create(
                    $this->get_meta_type(),
                    $this->get_meta_key($config),
                    $data_type
                );
            case FieldType::TYPE_POST:
                return $config->get('is_multiple', 'on') === 'on'
                    ? null
                    : (new Sorting\Model\RelatedMetaPostFactory())->create(
                        $this->get_meta_type(),
                        (string)$config->get('post'),
                        $this->get_meta_key($config)
                    );
            case FieldType::TYPE_USER :
                return (new Sorting\Model\RelatedMetaUserFactory())->create(
                    $this->get_meta_type(),
                    (string)$config->get('user'),
                    $this->get_meta_key($config)
                );
            case FieldType::TYPE_COUNT :
                return (new Sorting\Model\MetaCountFactory())->create(
                    $this->get_meta_type(),
                    $this->get_meta_key($config)
                );
            case FieldType::TYPE_SELECT :
                return $config->get('is_multiple', 'on') === 'on'
                    ? null
                    : $this->sorting_factory->create($this->get_meta_type(), $this->get_meta_key($config));
            case FieldType::TYPE_TEXT :
            case FieldType::TYPE_HTML :
            case FieldType::TYPE_NON_EMPTY :
            case FieldType::TYPE_IMAGE :
            case FieldType::TYPE_MEDIA :
            case FieldType::TYPE_URL :
            case FieldType::TYPE_COLOR :
            default :
                return $this->sorting_factory->create($this->get_meta_type(), $this->get_meta_key($config));
        }
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        switch ($this->get_field_type($config)) {
            case FieldType::TYPE_ARRAY:
                return new Search\Comparison\Meta\Serialized($this->get_meta_key($config));
            case FieldType::TYPE_BOOLEAN:
                return new Search\Comparison\Meta\Checkmark($this->get_meta_key($config));
            case FieldType::TYPE_DATE:
                return Search\Comparison\Meta\DateFactory::create(
                    $this->get_date_save_format($config),
                    $this->get_meta_key($config),
                    $this->create_query($this->get_meta_key($config))
                );
            case FieldType::TYPE_IMAGE:
            case FieldType::TYPE_MEDIA:
                return new Search\Comparison\Meta\Media(
                    $this->get_meta_key($config),
                    $this->create_query($this->get_meta_key($config))
                );
            case FieldType::TYPE_SELECT:
                $options = $this->get_select_options($config);

                return $config->get('is_multiple', 'on') === 'on'
                    ? new Search\Comparison\Meta\MultiSelect($this->get_meta_key($config), $options)
                    : new Search\Comparison\Meta\Select($this->get_meta_key($config), $options);

            case FieldType::TYPE_NUMERIC:
                return new Search\Comparison\Meta\Number($this->get_meta_key($config));
            case FieldType::TYPE_POST:
                return $config->get('is_multiple', 'on') === 'on'
                    ? new Search\Comparison\Meta\Posts(
                        $this->get_meta_key($config),
                        [],
                        [],
                        $this->create_query($this->get_meta_key($config)),
                        Search\Value::INT
                    )
                    : new Search\Comparison\Meta\Post(
                        $this->get_meta_key($config),
                        [],
                        [],
                        null,
                        $this->create_query($this->get_meta_key($config))
                    );
            case FieldType::TYPE_USER:
                return new Search\Comparison\Meta\User($this->get_meta_key($config));
            case FieldType::TYPE_COUNT:
                return null;
            case FieldType::TYPE_NON_EMPTY:
                return new Search\Comparison\Meta\EmptyNotEmpty($this->get_meta_key($config));
            case FieldType::TYPE_URL:
            case FieldType::TYPE_TEXT:
            case FieldType::TYPE_COLOR:
            default:
                return new Search\Comparison\Meta\SearchableText(
                    $this->get_meta_key($config),
                    $this->create_query($this->get_meta_key($config))
                );
        }
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        switch ((string)$config->get('field_type', '')) {
            case FieldType::TYPE_POST :
            case FieldType::TYPE_USER :
                return $this->get_formatters($config)
                            ->with_formatter(new AC\Formatter\StringSanitizer());
            case FieldType::TYPE_ARRAY :
            case FieldType::TYPE_COUNT :
                return $this->get_formatters($config);
            case FieldType::TYPE_NON_EMPTY :
                return new FormatterCollection([
                    new AC\Formatter\Meta($this->get_meta_type(), $this->get_meta_key($config)),
                    new AC\Formatter\HasValue(),
                    new AC\Formatter\BooleanLabel('1', '0'),
                ]);
            case FieldType::TYPE_DATE :

                $formatters = [
                    $this->get_meta_formatter($config),
                ];

                $source_format = $this->get_date_save_format($config);

                switch ($source_format) {
                    case DateSaveFormat::FORMAT_DATE :
                    case DateSaveFormat::FORMAT_DATETIME :
                        $formatters[] = new AC\Formatter\Date\DateFormat(
                            $source_format,
                            $source_format
                        );
                        break;
                    case DateSaveFormat::FORMAT_UNIX_TIMESTAMP :
                        $formatters[] = new AC\Formatter\Date\DateFormat(
                            DateSaveFormat::FORMAT_DATETIME,
                            DateSaveFormat::FORMAT_UNIX_TIMESTAMP
                        );
                        break;
                    default :
                        $formatters[] = new AC\Formatter\Date\Timestamp(); // Try to convert to timestamp first
                        $formatters[] = new AC\Formatter\Date\DateFormat(
                            DateSaveFormat::FORMAT_DATETIME,
                            DateSaveFormat::FORMAT_UNIX_TIMESTAMP
                        );
                }

                return new FormatterCollection($formatters);
            case FieldType::TYPE_IMAGE :
            case FieldType::TYPE_MEDIA :
                return new FormatterCollection([
                    $this->get_meta_formatter($config),
                    new AC\Formatter\ImageToCollection(),
                    new AC\Formatter\ImageUrl(),
                ]);
            case FieldType::TYPE_BOOLEAN :
            case FieldType::TYPE_COLOR :
            case FieldType::TYPE_TEXT :
            case FieldType::TYPE_URL :
            case FieldType::TYPE_NUMERIC :
            default :
                return FormatterCollection::from_formatter(
                    $this->get_meta_formatter($config)
                );
        }
    }

    private function get_meta_formatter(Config $config): AC\Formatter\Meta
    {
        return new AC\Formatter\Meta($this->get_meta_type(), $this->get_meta_key($config));
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        switch ($this->get_field_type($config)) {
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
                            $this->get_meta_formatter($config),
                            new AC\Formatter\Date\DateFormat('Y-m-d', $this->get_date_save_format($config)),
                        ])
                    )
                );
            case FieldType::TYPE_ARRAY :
                return $config->get('serialized_display', '') === 'formatted'
                    ? null
                    : new FormattableConfig();

            case FieldType::TYPE_NUMERIC :
            case FieldType::TYPE_COUNT :
                return new FormattableConfig(new IntegerFormatter());
            default:
                return new FormattableConfig();
        }
    }

    private function get_meta_type(): AC\MetaType
    {
        return $this->table_context->get_meta_type();
    }

    private function get_date_save_format(Config $config): string
    {
        $filter = new StoredDateFormat($this->get_context($config));

        return $filter->apply_filters((string)$config->get('date_save_format')) ?: 'Y-m-d';
    }

    private function get_select_options(Config $config): array
    {
        $data = json_decode($config->get('select_options', '')) ?? [];
        $options = [];

        foreach ($data as $option) {
            $options[$option->value] = $option->label;
        }

        return $options;
    }

    private function create_query(string $meta_key): Meta\Query
    {
        switch (true) {
            case $this->table_context->has_post_type():
                return (new Meta\QueryMetaFactory())->create_with_post_type(
                    $meta_key,
                    (string)$this->table_context->get_post_type()
                );
            default:
                return (new Meta\QueryMetaFactory())->create($meta_key, $this->get_meta_type());
        }
    }

    protected function get_context(Config $config): Context
    {
        return new CustomFieldContext(
            $config,
            $this->get_label(),
            $this->get_field_type($config),
            $this->get_meta_key($config),
            $this->table_context
        );
    }

}