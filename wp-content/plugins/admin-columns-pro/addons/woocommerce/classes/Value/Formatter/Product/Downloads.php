<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class Downloads extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ( ! $product->is_downloadable()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];
        $description = $this->get_description($product);

        foreach ($product->get_downloads() as $download) {
            $label = ac_helper()->html->link($download->get_file(), $download->get_name());
            $tooltip = wc_get_filename_from_url($download->get_file());

            $values[] = ac_helper()->html->tooltip($label, $tooltip . '<br>' . $description);
        }

        if ( ! $values) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            implode(', ', $values)
        );
    }

    private function get_description(WC_Product $product): string
    {
        $description = [];

        if (($limit = $product->get_download_limit()) > 0) {
            $description[] = __('Download limit', 'woocommerce') . ': ' . $limit;
        }

        if (($days = $product->get_download_expiry()) > 0) {
            $description[] = __('Download expiry', 'woocommerce') . ': ' . sprintf(
                    _n('%s day', '%s days', $days),
                    $days
                );
        }

        return implode('<br>', $description);
    }

}