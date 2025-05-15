<?php

namespace ACA\WC\Settings\HideOnScreen;

use ACP;

class FilterOrderSubType extends ACP\Settings\ListScreen\HideOnScreen
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_order_subtype',
            __('Order Type', 'codepress-admin-columns'),
            ACP\Settings\ListScreen\HideOnScreen\Filters::NAME
        );
    }

}