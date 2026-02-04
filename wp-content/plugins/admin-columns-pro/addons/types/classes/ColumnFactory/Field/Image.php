<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Field;
use ACA\Types\Value\Formatter\AttachmentUrl;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Image extends FieldFactory
{

    private ImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $table_context,
        Field $field,
        ImageSize $image_size
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $table_context,
            $field
        );
        $this->image_size = $image_size;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->image_size->create($config));
    }

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->add(new ACA\Types\Value\Formatter\AttachmentIdByUrl());
    }

    protected function get_post_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new Separator(''),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->get_base_formatters()
                    ->with_formatter(new AttachmentUrl());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = $this->field->is_repeatable()
            ? new Editing\Storage\RepeatableFile($this->field->get_meta_key(), $this->get_meta_type())
            : new Editing\Storage\File($this->field->get_meta_key(), $this->get_meta_type());

        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Image())->set_clear_button(true)->set_multiple($this->field->is_repeatable()),
            $storage
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\SearchableText(
            $this->field->get_meta_key(),
            (new ACA\Types\ContextQueryMetaFactory())->create_by_context($this->table_context, $this->field)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}