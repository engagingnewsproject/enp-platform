<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\ProductVariation;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class VariationDisplay extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'variation_display',
            OptionCollection::from_array([
                ''      => __('With label'),
                'short' => __('Without label', 'codepress-admin-columns'),
            ]),
            $config->get('variation_display', 'short')
        );
    }

}