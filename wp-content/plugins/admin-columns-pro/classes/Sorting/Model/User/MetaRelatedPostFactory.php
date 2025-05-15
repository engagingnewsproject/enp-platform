<?php

namespace ACP\Sorting\Model\User;

use AC\Settings\Column\Post;
use ACP\Sorting\Type\DataType;

/**
 * For sorting a user list table on a meta_key that holds a User ID (single).
 */
class MetaRelatedPostFactory
{

    public function create(string $post_property, string $meta_key)
    {
        switch ($post_property) {
            case Post::PROPERTY_TITLE :
                return new RelatedMeta\PostField('post_title', $meta_key);
            case Post::PROPERTY_ID :
                return new Meta($meta_key, new DataType(DataType::NUMERIC));
        }

        return null;
    }

}