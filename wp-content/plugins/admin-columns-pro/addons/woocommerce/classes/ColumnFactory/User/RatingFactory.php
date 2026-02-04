<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\User\RatingDisplay;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class RatingFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private RatingDisplay $rating_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        RatingDisplay $rating_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->rating_display = $rating_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->rating_display->create($config));
    }

    public function get_label(): string
    {
        return __('Ratings', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-ratings';
    }

    private function get_rating_type(Config $config): string
    {
        return (string)$config->get(RatingDisplay::NAME);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        if ($this->get_rating_type($config) === 'avg') {
            return $formatters->add(new Formatter\User\Ratings(true))->add(new Formatter\Stars());
        }

        return $formatters->add(new Formatter\User\Ratings(false));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $rating_type = 'avg' === $this->get_rating_type($config)
            ? 'AVG'
            : 'COUNT';

        return new Sorting\User\Ratings($rating_type);
    }

}