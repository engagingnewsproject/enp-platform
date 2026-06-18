<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterReadabilityScore extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_yoast_readability_score',
            __('Readability Score', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}