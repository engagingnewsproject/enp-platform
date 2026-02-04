<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\Password;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Sorting\DefaultSortingTrait;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\View;

class PasswordFactory extends FieldFactory
{

    use DefaultSortingTrait;

    private Password $password;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        Password $password,
        TableScreenContext $table_context
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $table_context
        );
        $this->password = $password;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->password->create($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Password())->set_clear_button(true)->set_placeholder($this->field->get_label())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(
            $this->field->get_name()
        );
    }

}