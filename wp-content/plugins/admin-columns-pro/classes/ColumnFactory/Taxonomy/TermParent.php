<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\TermLink;
use AC\Setting\ComponentFactory\TermProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;

class TermParent extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private TaxonomySlug $taxonomy;

    private TermProperty $term_property;

    private TermLink $term_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TermProperty $term_property,
        TermLink $term_link,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->term_property = $term_property;
        $this->term_link = $term_link;
        $this->taxonomy = $taxonomy;
    }

    public function get_column_type(): string
    {
        return 'column-term_parent';
    }

    public function get_label(): string
    {
        return __('Parent', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->term_property->create($config),
            $this->term_link->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->prepend(new AC\Formatter\Term\TermProperty('parent'));

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Taxonomy\TaxonomyParent((string)$this->taxonomy);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Taxonomy\ParentTerm((string)$this->taxonomy);
    }

}