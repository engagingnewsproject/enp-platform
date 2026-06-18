<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class Id extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    private BeforeAfter $before_after;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        BeforeAfter $before_after
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->before_after = $before_after;
    }

    public function get_column_type(): string
    {
        return 'column-termid';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add(
            $this->before_after->create($config)
        );
    }

    public function get_label(): string
    {
        return __('ID', 'codepress-admin-columns');
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('ID');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Taxonomy\ID();
    }

}