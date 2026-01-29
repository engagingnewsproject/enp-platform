<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy\Original;

use AC\Formatter\Term\TermProperty;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;

class Description extends OriginalColumnFactory
{

    private TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->taxonomy = $taxonomy;
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

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Taxonomy\Description();
    }

}