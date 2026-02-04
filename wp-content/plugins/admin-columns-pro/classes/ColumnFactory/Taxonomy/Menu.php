<?php

namespace ACP\ColumnFactory\Taxonomy;

use AC\Formatter\Collection\LocalizeSeparator;
use AC\Formatter\Term\TermProperty;
use AC\Formatter\UsedByMenu;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\LinkToMenu;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Sorting;

class Menu extends ACP\Column\AdvancedColumnFactory
{

    private LinkToMenu $link_to_menu_factory;

    private TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        LinkToMenu $link_to_menu_factory,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->link_to_menu_factory = $link_to_menu_factory;
        $this->taxonomy = $taxonomy;
    }

    public function get_column_type(): string
    {
        return 'column-used_by_menu';
    }

    public function get_label(): string
    {
        return __('Menu', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->link_to_menu_factory->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new UsedByMenu((string)$this->taxonomy),
            new TermProperty('name'),
            new LocalizeSeparator(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Menu(
            new Editing\Storage\Taxonomy\Menu((string)$this->taxonomy, 'taxonomy')
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Taxonomy\Menu((string)$this->taxonomy);
    }

}