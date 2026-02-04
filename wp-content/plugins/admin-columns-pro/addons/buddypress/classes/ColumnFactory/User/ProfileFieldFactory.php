<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\Formatter\SmallBlocks;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\WordLimit;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP;
use ACA\BP\Value\Formatter;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;
use ACP\Sorting\Type\DataType;
use BP_XProfile_Field;

class ProfileFieldFactory extends AdvancedColumnFactory
{

    private string $type;

    protected BP_XProfile_Field $field;

    private WordLimit $word_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        BP_XProfile_Field $field,
        WordLimit $word_limit
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->field = $field;
        $this->word_limit = $word_limit;
        $this->type = $type;
    }

    protected function get_group(): ?string
    {
        return 'buddypress_profile';
    }

    public function get_column_type(): string
    {
        return $this->type;
    }

    public function get_label(): string
    {
        return $this->field->name;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $components = new ComponentCollection([]);

        if ($this->field->type === 'textarea') {
            $components->add($this->word_limit->create($config));
        }

        return $components;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new Formatter\User\Profile\DisplayProfileFieldData($this->field->id),
        ]);

        $formatters->merge(parent::get_formatters($config));

        if ($this->field->type === 'checkbox' || $this->field->type === 'multiselectbox') {
            $formatters->add(new SmallBlocks());
        }

        return $formatters;
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        switch ($this->field->type) {
            case 'datebox':
                return new BP\Sorting\Profile($this->field->id, new DataType(DataType::DATETIME));
            case 'number':
                return new BP\Sorting\Profile($this->field->id, new DataType(DataType::NUMERIC));
            default:
                return new BP\Sorting\Profile($this->field->id);
        }
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        switch ($this->field->type) {
            case 'checkbox':
            case 'multiselectbox':
                return new BP\Search\Profile\MultipleChoice($this->field->id, $this->get_options());
            case 'datebox':
                return new BP\Search\Profile\Date($this->field->id);
            case 'number':
                return new BP\Search\Profile\Number($this->field->id);
            case'radio':
            case'selectbox':
                return new BP\Search\Profile\Choice($this->field->id, $this->get_options());
            default:
                return new BP\Search\Profile\Text($this->field->id);
        }
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        switch ($this->field->type) {
            case 'checkbox':
            case 'multiselectbox':
                return new Editing\Service\Basic(
                    (new Editing\View\CheckboxList($this->get_options())),
                    new BP\Editing\Storage\Profile\MultiChoices($this->field->id)
                );

            case 'datebox':
                return new Editing\Service\Date(
                    (new Editing\View\Date())->set_clear_button(true),
                    new BP\Editing\Storage\Profile($this->field->id),
                    'Y-m-d 00:00:00'
                );
            case 'number':
                return new Editing\Service\Basic(
                    (new Editing\View\Number()),
                    new BP\Editing\Storage\Profile($this->field->id)
                );
            case 'textarea':
                return new Editing\Service\Basic(
                    (new Editing\View\TextArea())->set_clear_button(true),
                    new BP\Editing\Storage\Profile($this->field->id)
                );
            case 'radio':
            case 'selectbox':
                return new Editing\Service\Basic(
                    (new Editing\View\Select($this->get_options())),
                    new BP\Editing\Storage\Profile($this->field->id)
                );
            default:
                return new Editing\Service\Basic(
                    (new Editing\View\Text())->set_clear_button(true),
                    new BP\Editing\Storage\Profile($this->field->id)
                );
        }
    }

    private function get_options(): array
    {
        $options = [];
        foreach ($this->field->get_children() as $option) {
            $options[$option->name] = $option->name;
        }

        return $options;
    }

}