<?php

declare(strict_types=1);

namespace ACP\Helper\Select\Taxonomy\GroupFormatter;

use AC\Helper;
use ACP\Helper\Select\Taxonomy\GroupFormatter;
use WP_Term;

class Taxonomy implements GroupFormatter
{

    public function format(WP_Term $term): string
    {
        return Helper\Taxonomy::create()->get_taxonomy_label($term->taxonomy);
    }

}