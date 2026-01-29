<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\StringLimit;
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

class WysiwygFactory extends FieldFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use DefaultSortingTrait;

    private StringLimit $string_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        StringLimit $string_limit,
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
        $this->string_limit = $string_limit;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->string_limit->create($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            new View\TextArea()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(
            $this->field->get_name()
        );
    }

}