<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Value\Formatter\IdCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\View;
use ACP\Sorting\Type\DataType;

class FileFactory extends FieldFactory
{

    use MetaQueryTrait;

    private ImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        ImageSize $image_size,
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
        $this->image_size = $image_size;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = parent::get_settings($config);

        switch ($this->get_file_type()) {
            case 'images' :
            case 'images-any' :
            case 'any' :
                $settings->add($this->image_size->create($config));
                break;
        }

        return $settings;
    }

    private function is_multiple(): bool
    {
        return $this->field->get_field()->get_arg('file_format_type') === 'multi';
    }

    private function get_file_type(): string
    {
        return $this->field->get_field()->get_arg('file_type', 'any');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        switch ($this->get_file_type()) {
            case 'images':
                $view = new View\Image();
                break;
            case 'video':
                $view = new View\Video();
                break;
            case 'audio':
                $view = new View\Audio();
                break;
            default:
                $view = new View\Media();
        }

        $view->set_multiple($this->is_multiple());

        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            $view
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->get_base_formatters($config)
                    ->with_formatter(new AC\Formatter\Media\AttachmentUrl());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Media(
            $this->field->get_name(),
            $this->get_query_meta($this->get_post_type())
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create(
            $this->field->get_meta_type(),
            $this->field->get_name(),
            new DataType(DataType::NUMERIC)
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)
                     ->add(new IdCollection());
    }

}