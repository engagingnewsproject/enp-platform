<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Settings\ListScreen\TableElement;

use ACP\Settings\ListScreen\TableElement;

class FilterSeoScores extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_yoast_seo_scores',
            __('SEO Scores', 'codepress-admin-columns'),
            'element',
            TableElement\Filters::NAME
        );
    }

}