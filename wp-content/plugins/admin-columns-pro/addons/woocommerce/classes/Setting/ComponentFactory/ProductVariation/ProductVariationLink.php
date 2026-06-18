<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\ProductVariation;

use AC\FormatterCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;
use ACA\WC\Value\Formatter;

class ProductVariationLink extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Link to', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_select(
            'product_link_to',
            OptionCollection::from_array($this->get_display_options()),
            (string)$config->get('product_link_to', '')
        );
    }

    protected function get_display_options(): array
    {
        return [
            ''               => __('None'),
            'edit_variation' => __('Edit Product Variation', 'codepress-admin-columns'),
        ];
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        if ($config->get('product_link_to') === 'edit_variation') {
            $formatters->add(new Formatter\ProductVariation\EditVariationLink());
        }
    }

}