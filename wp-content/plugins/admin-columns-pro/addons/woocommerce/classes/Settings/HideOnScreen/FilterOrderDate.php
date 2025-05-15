<?php

namespace ACA\WC\Settings\HideOnScreen;

use ACP;

class FilterOrderDate extends ACP\Settings\ListScreen\HideOnScreen
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_order_date',
            __('Date', 'codepress-admin-columns'),
            ACP\Settings\ListScreen\HideOnScreen\Filters::NAME
        );
    }

}