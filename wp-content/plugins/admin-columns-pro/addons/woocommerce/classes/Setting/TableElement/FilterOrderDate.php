<?php

declare(strict_types=1);

namespace ACA\WC\Setting\TableElement;

use ACP;

class FilterOrderDate extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_order_date',
            __('Date', 'codepress-admin-columns'),
            'element',
            ACP\Settings\ListScreen\TableElement\Filters::NAME
        );
    }

}