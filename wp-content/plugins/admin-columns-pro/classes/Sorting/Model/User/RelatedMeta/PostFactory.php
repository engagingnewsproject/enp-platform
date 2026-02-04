<?php

namespace ACP\Sorting\Model\User\RelatedMeta;

use AC\Setting\ComponentFactory\PostProperty;
use ACP\Sorting\Model\User\Meta;
use ACP\Sorting\Type\DataType;

/**
 * For sorting a user list table on a meta_key that holds a User ID (single).
 */
class PostFactory
{

    public function create(string $post_property, string $meta_key)
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