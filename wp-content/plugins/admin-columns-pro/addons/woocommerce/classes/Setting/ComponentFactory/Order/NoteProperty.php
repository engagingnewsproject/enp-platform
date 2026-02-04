<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class NoteProperty extends BaseComponentFactory
{

    public const NAME = 'note_property';
    public const PROPERTY_COUNT = 'count';
    public const PROPERTY_LATEST = 'latest';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                self::PROPERTY_COUNT  => __('Count', 'codepress-admin-columns'),
                self::PROPERTY_LATEST => __('Last Order Note', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, self::PROPERTY_COUNT)
        );
    }

}