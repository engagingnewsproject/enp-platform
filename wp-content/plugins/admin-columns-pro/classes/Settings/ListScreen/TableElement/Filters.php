<?php

namespace ACP\Settings\ListScreen\TableElement;

use ACP;

class Filters extends ACP\Settings\ListScreen\TableElement
{

    public const NAME = 'hide_filters';

    public function __construct()
    {
        parent::__construct(self::NAME, __('Filters', 'codepress-admin-columns'), 'element');
    }

}