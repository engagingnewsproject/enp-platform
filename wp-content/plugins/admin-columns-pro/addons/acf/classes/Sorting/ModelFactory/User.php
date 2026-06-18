<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting\ModelFactory;

use AC\Setting\Component;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\ACF\Field;
use ACP;
use ACP\Sorting\Model\MetaFormatFactory;
use ACP\Sorting\Model\RelatedMetaUserFactory;

class User
{

    private MetaFormatFactory $meta_format_factory;

    public function __construct()
    {
        $this->meta_format_factory = new MetaFormatFactory();
    }

    public function create(Field $field, string $meta_key, TableScreenContext $table_context, Config $config)
    {
        $setting = (new UserProperty())->create($config);

        return $field instanceof Field\Multiple && $field->is_multiple()
            ? $this->create_multiple_relation_model($table_context, $meta_key, $setting)
            : $this->create_single_relation_model($table_context, $meta_key, $setting);
    }

    private function create_single_relation_model(
        TableScreenContext $table_context,
        string $meta_key,
        Component $setting
    ) {
        $model = (new RelatedMetaUserFactory())->create(
            $table_context->get_meta_type(),
            $setting->get_input()->get_value(),
            $meta_key
        );

        return $model
            ?: $this->meta_format_factory->create(
                $table_context->get_meta_type(),
                $meta_key,
                new ACP\Sorting\FormatValue\SettingFormatter($setting->get_formatters()),
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
        Component $setting
    ) {
        return $this->meta_format_factory->create(
            $table_context->get_meta_type(),
            $meta_key,
            new ACP\Sorting\FormatValue\SerializedSettingFormatter(
                new ACP\Sorting\FormatValue\SettingFormatter($setting->get_formatters())
            ),
            null,
            [
                'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
            ]
        );
    }

}