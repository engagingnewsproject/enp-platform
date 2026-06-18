<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\YoastSeo\Editing\Service\User\ToggleOn;
use ACP\Column\AdvancedColumnFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class DisableReadabilityAnalysisFactory extends AdvancedColumnFactory
{

    private const META_KEY = 'wpseo_content_analysis_disable';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_disable_readability_analysis';
    }

    public function get_label(): string
    {
        return __('Disable readability analysis', 'wordpress-seo');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\User\Meta(self::META_KEY))
                     ->add(new AC\Formatter\YesNoIcon());
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Checkmark(self::META_KEY);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new ToggleOn(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta(self::META_KEY);
    }

}