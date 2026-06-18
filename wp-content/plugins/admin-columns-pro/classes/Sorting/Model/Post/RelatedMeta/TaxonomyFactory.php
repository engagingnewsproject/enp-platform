<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post\RelatedMeta;

use ACP\Sorting\Model\Post\Meta;
use ACP\Sorting\Model\Post\RelatedMeta;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Type\DataType;

/**
 * For sorting a post list table on a meta_key that holds a Term ID (single).
 */
class TaxonomyFactory
{

    public function create(string $term_property, string $meta_key): ?QueryBindings
    {
        switch ($term_property) {
            case 'name':
            case 'slug':
                return new RelatedMeta\Taxonomy\TermField($term_property, $meta_key);
            case 'id':
                return new Meta($meta_key, DataType::create_numeric());
            case '':
                return new RelatedMeta\Taxonomy\TermField('name', $meta_key);
        }

        return null;
    }

}