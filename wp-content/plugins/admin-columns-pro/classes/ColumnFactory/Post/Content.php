<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Content extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private Editing\Setting\ComponentFactory\InlineEditContentTypeFactory $editable_component;

    public function __construct(
        AC\ColumnFactory\Post\ContentFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        Editing\Setting\ComponentFactory\InlineEditContentTypeFactory $editable_component
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
        $this->editable_component = $editable_component;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit(
                         $this->editable_component->create(
                             new Editing\Setting\ComponentFactory\EditableType\Content()
                         )
                     );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\PostContent();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Property('post_content'));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return 'wysiwyg' === $config->get('editable_type', '')
            ? new Editing\Service\Post\ContentWysiwyg()
            : new Editing\Service\Post\Content();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Post\Content();
    }

}