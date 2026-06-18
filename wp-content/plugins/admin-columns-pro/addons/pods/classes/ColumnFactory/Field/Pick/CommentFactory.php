<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\CommentDisplay;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Search;
use ACA\Pods\Value\Formatter\IdCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class CommentFactory extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private CommentDisplay $comment_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        CommentDisplay $comment_display,
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
        $this->comment_display = $comment_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->comment_display->create($config));
    }

    private function is_multiple(): bool
    {
        return 'multi' === $this->field->get_field()->get_arg('pick_format_type');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\PickComments(
            new Editing\Storage\Field(
                $this->field,
                new Editing\Storage\Read\DbRaw($this->field->get_name(), $this->field->get_meta_type())
            ),
            $this->is_multiple()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\PickComment($this->field->get_name());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->field->get_meta_type(), $this->field->get_name());
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)->add(new IdCollection('comment_ID'));
    }

}