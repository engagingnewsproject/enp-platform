<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Setting\ComponentFactory;

use AC\Type\PostTypeSlug;

class PrimaryTaxonomyFactory
{

    public function create(PostTypeSlug $post_type): PrimaryTaxonomy
    {
        return new PrimaryTaxonomy($post_type);
    }

}