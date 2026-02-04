<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post\RelatedMeta;

use AC\Setting\ComponentFactory\PostProperty;
use ACP\Sorting\Model\Post\Meta;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Type\DataType;

/**
 * For sorting a post list table on a meta_key that holds a Post ID (single).
 */
class PostFactory
{

    public function create(string $post_property, string $meta_key): ?QueryBindings
    {
        switch ($post_property) {
            case PostProperty::PROPERTY_TITLE :
                return new Post\Field('post_title', $meta_key);
            case PostProperty::PROPERTY_ID :
                return new Meta($meta_key, new DataType(DataType::NUMERIC));
        }

        return null;
    }

}