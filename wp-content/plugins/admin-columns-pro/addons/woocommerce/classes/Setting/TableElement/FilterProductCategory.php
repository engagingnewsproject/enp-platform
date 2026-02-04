<?php

declare(strict_types=1);

namespace ACA\WC\Setting\TableElement;

use ACP;

class FilterProductCategory extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_product_category',
            __('Category', 'codepress-admin-columns'),
            'element',
            ACP\Settings\ListScreen\TableElement\Filters::NAME
        );
    }

}