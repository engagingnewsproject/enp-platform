<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Post;

use AC;

class TaxonomyTermFactory
{

    public function create(AC\Type\PostTypeSlug $post_type): TaxonomyTerm
    {
        return new TaxonomyTerm($post_type);
    }

}