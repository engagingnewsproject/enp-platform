<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface TaxonomyFilterable
{

    public function get_taxonomies(): array;

}