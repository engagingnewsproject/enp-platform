<?php

namespace ACP\Settings\Option;

use AC\Settings\GeneralOption;

class LayoutStyle
{

    public const OPTION_TABS = 'tabs';
    public const OPTION_DROPDOWN = 'dropdown';

    private GeneralOption $storage;

    public function __construct(GeneralOption $storage)
    {
        $this->storage = $storage;
    }

    public function get_style(): string
    {
        return $this->storage->get('layout_style') ?: self::OPTION_TABS;
    }

}