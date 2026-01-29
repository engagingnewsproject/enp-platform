<?php

declare(strict_types=1);

namespace ACA\WC\Setting\TableElement;

use ACP;

class FilterOrderSubType extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_order_subtype',
            __('Order Type', 'codepress-admin-columns'),
            'element',
            ACP\Settings\ListScreen\TableElement\Filters::NAME
        );
    }

}