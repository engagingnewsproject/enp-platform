<?php

namespace ACP\Settings\General;

use AC\Storage\GeneralOption;

class LayoutStyle
{

    public const OPTION_TABS = 'tabs';
    public const OPTION_DROPDOWN = 'dropdown';

    private $storage;

    public function __construct(GeneralOption $storage)
    {
        $this->storage = $storage;
    }

    public function get_style(): string
    {
        $style = $this->storage->find('layout_style');

        if (self::OPTION_TABS !== $style) {
            $style = self::OPTION_DROPDOWN;
        }

        return $style;
    }

}