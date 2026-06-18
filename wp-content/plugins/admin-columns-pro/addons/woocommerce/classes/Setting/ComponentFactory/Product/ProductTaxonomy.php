<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Product;

use AC\Setting\ComponentFactory\Taxonomy;
use AC\Type\PostTypeSlug;

class ProductTaxonomy extends Taxonomy
{

    public function __construct()
    {
        parent::__construct(new PostTypeSlug('product'));
    }

}