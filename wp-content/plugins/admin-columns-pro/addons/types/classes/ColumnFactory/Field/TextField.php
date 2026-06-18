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
use ACP\Column\FeatureSettingBuilderFactory;

class TextField extends FieldFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    private AC\Setting\ComponentFactory\StringLimit $string_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $table_context,
        Field $field,
        AC\Setting\ComponentFactory\StringLimit $string_limit
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $table_context,
            $field
        );
        $this->string_limit = $string_limit;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->string_limit->create($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        if ($this->field->is_repeatable()) {
            return new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\MultiInput())->set_clear_button(true),
                new Editing\Storage\Repeater($this->field->get_meta_key(), $this->get_meta_type())
            );
        }

        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->field->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}