<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Field;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\View;
use ACP\Sorting\Type\DataType;

class Date extends FieldFactory
{

    private AC\Setting\ComponentFactory\DateFormat\Date $date_format;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_date;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $table_context,
        Field $field,
        AC\Setting\ComponentFactory\DateFormat\Date $date_format,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_date
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $table_context,
            $field
        );
        $this->date_format = $date_format;
        $this->filter_date = $filter_date;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)->set_search(null, $this->filter_date);
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->date_format->create($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        if ($this->field->is_repeatable()) {
            return new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\MultiInput())->set_clear_button(true)->set_sub_type('date'),
                new Editing\Storage\RepeatableDate($this->field->get_meta_key(), $this->get_meta_type())
            );
        }

        if ( ! $this->field->is_repeatable()) {
            $storage = new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type());

            return $this->has_time()
                ? new ACP\Editing\Service\DateTime((new View\DateTime())->set_clear_button(true), $storage, 'U')
                : new ACP\Editing\Service\Date((new View\Date())->set_clear_button(true), $storage, 'U');
        }

        return null;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
            $this->field->get_meta_key(),
            (new ACA\Types\ContextQueryMetaFactory())->create_by_context($this->table_context, $this->field)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create(
                $this->get_meta_type(),
                $this->field->get_meta_key(),
                new DataType(DataType::NUMERIC)
            );
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new AC\FormatterCollection([
                    new AC\Formatter\MetaCollection($this->get_meta_type(), $this->field->get_meta_key()),
                ]),
                'U'
            )
        );
    }

    private function has_time(): bool
    {
        return 'and_time' === $this->field->get_data('date_and_time');
    }

}