<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Field;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Post extends FieldFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private ComponentFactory\PostProperty $post_property;

    private ComponentFactory\PostLink $post_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $table_context,
        Field $field,
        ComponentFactory\PostProperty $post_property,
        ComponentFactory\PostLink $post_link
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $table_context,
            $field
        );
        $this->post_property = $post_property;
        $this->post_link = $post_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->post_property->create($config))
                     ->add($this->post_link->create($config));
    }

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->prepend(
            new ACA\Types\Value\Formatter\PostReferenceId($this->field->get_id())
        );
    }

}