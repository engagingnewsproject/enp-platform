<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory;

use AC\Expression\StringComparisonSpecification;
use AC\Setting\Children;
use AC\Setting\Component;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class PeriodWithAllTime extends BaseComponentFactory
{

    public const NAME = 'period';
    private const OPTION_CUSTOM = 'custom';

    protected function get_label(Config $config): ?string
    {
        return __('Period', 'codepress-admin-columns');
    }

    protected function get_description(Config $config): ?string
    {
        return __('Select the time period from now', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                ''     => __('All time', 'codepress-admin-columns'),
                '365'  => __('Last year', 'codepress-admin-columns'),
                '92'   => __('Last quarter', 'codepress-admin-columns'),
                '31'   => __('Last month', 'codepress-admin-columns'),
                '7'    => __('Last week', 'codepress-admin-columns'),
                self::OPTION_CUSTOM => __('Custom', 'codepress-admin-columns'),
            ]),
            (string)$config->get(self::NAME, '')
        );
    }

    protected function get_children(Config $config): ?Children
    {
        $value = $config->get('period_custom');

        return new Children(
            new ComponentCollection([
                new Component(
                    __('Number of days', 'codepress-admin-columns'),
                    null,
                    Input\Number::create_single_step(
                        'period_custom',
                        1,
                        null,
                        $value ? (int)$value : null,
                        '14'
                    ),
                    StringComparisonSpecification::equal(self::OPTION_CUSTOM)
                ),
            ])
        );
    }

}
