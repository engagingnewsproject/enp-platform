<?php

namespace ACP\Sorting\Model;

use AC\MetaType;

/**
 * For sorting a list table (e.g. post or user) on a meta_key that holds a User ID (single).
 */
class RelatedMetaUserFactory
{

    public function create(MetaType $meta_type, string $user_property, string $meta_key)
    {
        switch ((string)$meta_type) {
            case MetaType::POST :
                return (new Post\RelatedMeta\UserFactory())->create($user_property, $meta_key);
            case MetaType::USER :
                return (new User\RelatedMeta\UserFactory())->create($user_property, $meta_key);
        }

        return null;
    }

}