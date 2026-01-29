<?php

namespace ACP\Sorting\Model;

use AC\MetaType;

class RelatedMetaPostFactory
{

    public function create(MetaType $meta_type, string $post_property, string $meta_key)
    {
        switch ((string)$meta_type) {
            case MetaType::POST :
                return (new Post\RelatedMeta\PostFactory())->create($post_property, $meta_key);
            case MetaType::USER :
                return (new User\RelatedMeta\PostFactory())->create($post_property, $meta_key);
        }

        return null;
    }

}