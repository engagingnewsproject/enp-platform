<?php

declare(strict_types=1);

namespace ACA\WC\Setting\TableElement;

use ACP;

class FilterOrderCustomer extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_order_customer',
            __('Registered Customer', 'codepress-admin-columns'),
            'element',
            ACP\Settings\ListScreen\TableElement\Filters::NAME
        );
    }

}