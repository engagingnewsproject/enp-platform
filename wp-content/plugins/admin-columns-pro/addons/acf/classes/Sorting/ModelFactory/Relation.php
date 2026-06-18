<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting\ModelFactory;

use AC;
use AC\Setting\Component;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\ACF\Field;
use ACP\Sorting\FormatValue\SerializedSettingFormatter;
use ACP\Sorting\FormatValue\SettingFormatter;
use ACP\Sorting\Model\MetaFormatFactory;
use ACP\Sorting\Model\RelatedMetaPostFactory;

class Relation
{

    private MetaFormatFactory $meta_format_factory;

    private AC\Setting\ComponentFactory\PostProperty $post_property;

    public function __construct(
        MetaFormatFactory $meta_format_factory,
        AC\Setting\ComponentFactory\PostProperty $post_property
    ) {
        $this->post_property = $post_property;
        $this->meta_format_factory = $meta_format_factory;
    }

    public function create(Field $field, string $meta_key, TableScreenContext $table_context, Config $config)
    {
        $setting = $this->post_property->create($config);

        return $field instanceof Field\Multiple && $field->is_multiple()
            ? $this->create_multiple_relation_model($table_context, $meta_key, $setting)
            : $this->create_single_relation_model($table_context, $meta_key, $setting);
    }

    private function create_single_relation_model(
        TableScreenContext $table_context,
        string $meta_key,
        Component $component
    ) {
        $model = (new RelatedMetaPostFactory())->create(
            $table_context->get_meta_type(),
            $component->get_input()->get_value(),
            $meta_key
        );

        return $model
            ?: $this->meta_format_factory->create(
                $table_context->get_meta_type(),
                $meta_key,
                new SettingFormatter($component->get_formatters()),
                null,
                [
                    'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                    'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
                ]
            );
    }

    private function create_multiple_relation_model(
        TableScreenContext $table_context,
        string $meta_key,
        Component $component
    ) {
        return $this->meta_format_factory->create(
            $table_context->get_meta_type(),
            $meta_key,
            new SerializedSettingFormatter(new SettingFormatter($component->get_formatters())),
            null,
            [
                'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
            ]
        );
    }

}