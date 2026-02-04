<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Type\Value;

class PostsHavingFeaturedImageCollection implements AC\Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
                SELECT pm.post_id 
                FROM $wpdb->postmeta AS pm
                    JOIN $wpdb->posts AS pp ON pp.ID = pm.post_id 
                WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
            $value->get_id()
        );

        return AC\Type\ValueCollection::from_ids($value->get_id(), $wpdb->get_col($sql));
    }

}