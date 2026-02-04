<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy;

use AC\Formatter\Term\TermProperty;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\WordLimit;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;

class Description extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private TaxonomySlug $taxonomy;

    private WordLimit $word_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        WordLimit $word_limit,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->word_limit = $word_limit;
        $this->taxonomy = $taxonomy;
    }

    public function get_column_type(): string
    {
        return 'column-excerpt';
    }

    public function get_label(): string
    {
        return __('Description', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->word_limit->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->prepend(new TermProperty('description'));

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\TextArea(),
            new Editing\Storage\Taxonomy\Field((string)$this->taxonomy, 'description')
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new TermProperty('description'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Taxonomy\Description();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Taxonomy\Description();
    }

}