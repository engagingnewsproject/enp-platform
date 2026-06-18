<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event\AdditionalFields;

use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACA\EC\AdditionalField;
use ACA\EC\Service\ColumnGroups;
use ACA\EC\Value\Formatter\Event\FieldValue;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class AdditionalFieldFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private string $type;

    private AdditionalField $field;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AdditionalField $field,
        string $type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->field = $field;
        $this->type = $type;
    }

    protected function get_group(): ?string
    {
        return ColumnGroups::EVENTS_CALENDAR_FIELDS;
    }

    public function get_column_type(): string
    {
        return $this->type;
    }

    public function get_label(): string
    {
        return $this->field->get_label();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new FieldValue($this->field->get_label()),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())
            ->create(new MetaType(MetaType::POST), $this->field->get_id());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            $this->get_editing_view($this->field->get_type()),
            new ACP\Editing\Storage\Post\Meta($this->field->get_id())
        );
    }

    private function get_editing_view(string $type): ?ACP\Editing\View
    {
        switch ($type) {
            case 'checkbox':
                return (new  ACP\Editing\View\CheckboxList($this->field->get_select_options()))->set_clear_button(true);
            case 'dropdown':
            case 'radio':
                return (new ACP\Editing\View\Select($this->field->get_select_options()))->set_clear_button(true);
            case 'textarea':
                return new ACP\Editing\View\TextArea();
            case 'url':
                return (new ACP\Editing\View\Url())->set_clear_button(true);
            default:
                return (new ACP\Editing\View\Text())->set_clear_button(true);
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($this->field->get_type()) {
            case 'checkbox':
                return new EC\Search\Event\Field\MultipleOptions(
                    $this->field->get_id(),
                    $this->field->get_select_options()
                );
            case 'dropdown':
            case 'radio':
                return new EC\Search\Event\Field\Options($this->field->get_id(), $this->field->get_select_options());

            default:
                return new ACP\Search\Comparison\Meta\Text($this->field->get_id());
        }
    }

}