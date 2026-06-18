<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class GalleryIds implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $ids = $product->get_gallery_image_ids();

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}