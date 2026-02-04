<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;
use WC_Product_Download;

class DownloadFiles extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ( ! $product->is_downloadable()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        /**
         * @var WC_Product_Download $download
         */
        foreach ($product->get_downloads() as $download) {
            $values[] = $download->get_file();
        }

        return $value->with_value(implode(', ', $values));
    }

}