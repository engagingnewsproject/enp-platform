<?php

namespace ACP\ColumnFactory\User;

use AC;
use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Setting\ComponentFactory\User\UserTaxonomy;

class TaxonomyFactory extends ACP\Column\AdvancedColumnFactory
{

    private UserTaxonomy $taxonomy_factory;

    private ComponentFactory\NumberOfItems $number_of_items_factory;

    private ComponentFactory\Separator $separator_factory;

    private Editing\Setting\ComponentFactory\InlineEditCreateTerms $inline_edit_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UserTaxonomy $taxonomy_factory,
        ComponentFactory\NumberOfItems $number_of_items_factory,
        ComponentFactory\Separator $separator_factory,
        Editing\Setting\ComponentFactory\InlineEditCreateTerms $inline_edit_factory
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);

        $this->separator_factory = $separator_factory;
        $this->number_of_items_factory = $number_of_items_factory;
        $this->taxonomy_factory = $taxonomy_factory;
        $this->inline_edit_factory = $inline_edit_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);
        $builder->set_edit($this->inline_edit_factory);

        return $builder;
    }

    public function get_column_type(): string
    {
        return 'column-taxonomy';
    }

    public function get_label(): string
    {
        return __('Taxonomy', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->taxonomy_factory->create($config),
            $this->number_of_items_factory->create($config),
            $this->separator_factory->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new ACP\Formatter\User\UserTerms((string)$config->get('taxonomy', '')))
                     ->add(new AC\Formatter\Term\TermProperty('name'))
                     ->add(new AC\Formatter\Term\TermLink('edit'))
                     ->add(Separator::create_from_config($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Taxonomy(
            (string)$config->get('taxonomy', ''),
            'on' === (string)$config->get('enable_term_creation', 'on'),
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Taxonomy((string)$config->get('taxonomy', ''));
    }

}