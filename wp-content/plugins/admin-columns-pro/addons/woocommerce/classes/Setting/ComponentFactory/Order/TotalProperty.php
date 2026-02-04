<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class TotalProperty extends BaseComponentFactory
{

    public const NAME = 'order_total_property';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_display_options(),
            $config->get(self::NAME, 'total'),
            null,
            false,
            new AttributeCollection([
                AttributeFactory::create_refresh(),
            ])
        );
    }

    protected function get_display_options(): OptionCollection
    {
        $options = [
            'total'    => __('Total', 'codepress-admin-columns'),
            'subtotal' => __('Subtotal', 'codepress-admin-columns'),
            'shipping' => __('Shipping Costs', 'codepress-admin-columns'),
            'tax'      => __('Tax', 'codepress-admin-columns'),
            'refunded' => __('Refunds', 'codepress-admin-columns'),
            'discount' => __('Discounts', 'codepress-admin-columns'),
            'paid'     => __('Paid', 'codepress-admin-columns'),
            'fees'     => __('Fees', 'codepress-admin-columns'),
        ];

        natcasesort($options);

        return OptionCollection::from_array($options);
    }

}