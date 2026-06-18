<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Formatter\Date\Timestamp;
use AC\FormatterCollection;
use AC\Meta\QueryMetaFactory;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\DateFormat\Date;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Value\Formatter\PodsFieldRaw;
use ACA\Pods\Value\Formatter\PodsNullDates;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Editing\View;
use ACP\Sorting\Type\DataType;

class DateFactory extends FieldFactory
{

    private Date $date_format;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_date;

    private ?PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        Date $date_format,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_date,
        TableScreenContext $table_context,
        ?PostTypeSlug $post_type = null
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $table_context
        );
        $this->date_format = $date_format;
        $this->filter_date = $filter_date;
        $this->post_type = $post_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->date_format->create($config));
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->filter_date);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Date())->set_clear_button(true)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $query = (new QueryMetaFactory())->create($this->field->get_name(), $this->field->get_meta_type());
        if ($this->post_type) {
            $query->where_post_type((string)$this->post_type);
        }

        return new ACP\Search\Comparison\Meta\Date(
            $this->field->get_name(), $query
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create(
            $this->field->get_meta_type(),
            $this->field->get_name(),
            new DataType(DataType::DATE)
        );
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new FormatterCollection([
                    new PodsFieldRaw($this->field, true),
                ]),
                'Y-m-d'
            )
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)
                     ->add(new PodsNullDates())
                     ->add(new Timestamp());
    }

}