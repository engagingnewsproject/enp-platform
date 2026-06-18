<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class NoteType extends BaseComponentFactory
{

    public const NAME = 'note_type';
    public const SYSTEM_NOTE = 'system';
    public const PRIVATE_NOTE = 'private';
    public const CUSTOMER_NOTE = 'customer';

    protected function get_label(Config $config): ?string
    {
        return __('Note Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                ''                  => __('All Notes', 'codepress-admin-columns'),
                self::SYSTEM_NOTE   => __('System Notes', 'codepress-admin-columns'),
                self::PRIVATE_NOTE  => __('Private Notes', 'codepress-admin-columns'),
                self::CUSTOMER_NOTE => __('Notes to Customer', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, '')
        );
    }

}