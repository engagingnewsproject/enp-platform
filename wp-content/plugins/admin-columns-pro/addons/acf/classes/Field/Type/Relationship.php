<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Relationship extends Field
    implements Field\PostTypeFilterable, Field\TaxonomyFilterable, Field\Multiple
{

    use PostTypeTrait;
    use TaxonomyFilterableTrait;

    public function is_multiple(): bool
    {
        return true;
    }

}