<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory;

use AC\Type\TaxonomySlug;

class TaxonomyPostTypeFactory
{

    public function create(TaxonomySlug $taxonomy): TaxonomyPostType
    {
        return new TaxonomyPostType($taxonomy);
    }

}