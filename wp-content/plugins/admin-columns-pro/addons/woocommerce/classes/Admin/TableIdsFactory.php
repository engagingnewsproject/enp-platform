<?php

declare(strict_types=1);

namespace ACA\WC\Admin;

use AC;
use AC\Type\TableId;
use AC\Type\TableIdCollection;

class TableIdsFactory implements AC\TableIdsFactory
{

    public function create(): TableIdCollection
    {
        $collection = new TableIdCollection();

        $keys = [
            'wc_order',
            'wc_order_subscription',
        ];

        foreach (wc_get_attribute_taxonomy_names() as $taxonomy) {
            $keys[] = 'wp-taxonomy_' . $taxonomy;
        }

        foreach ($keys as $key) {
            $collection->add(new TableId($key));
        }

        return $collection;
    }

}