<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACA\RankMath\Editing;
use ACA\RankMath\Search;
use ACA\RankMath\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

final class Robots extends AdvancedColumnFactory
{

    use GroupTrait;

    private const META_KEY = 'rank_math_robots';

    private MetaType $meta_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        MetaType $meta_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->meta_type = $meta_type;
    }

    public function get_column_type(): string
    {
        return 'column-rankmath-robots';
    }

    public function get_label(): string
    {
        return _x('All Directives', 'Rank Math Robots Meta', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Value\Formatter\Robots($this->meta_type));
    }

    protected function get_group(): string
    {
        return 'rank-math-robots-meta';
    }

}