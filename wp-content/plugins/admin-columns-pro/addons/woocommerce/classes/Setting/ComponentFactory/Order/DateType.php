<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class DateType extends BaseComponentFactory
{

    public const NAME = 'date_type';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        $dates = $this->get_display_options();
        $default_date = $dates->first();
        $default = $default_date
            ? $default_date->get_value()
            : null;

        return Input\OptionFactory::create_select(
            self::NAME,
            $dates,
            $config->get(self::NAME, $default),
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
            'completed' => __('Completed', 'codepress-admin-columns'),
            'created'   => __('Created', 'codepress-admin-columns'),
            'modified'  => __('Modified', 'codepress-admin-columns'),
            'paid'      => __('Paid', 'codepress-admin-columns'),
        ];

        natcasesort($options);

        return OptionCollection::from_array($options);
    }

}