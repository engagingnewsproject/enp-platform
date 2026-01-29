<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_DateTime;
use WC_Order;

class Downloads extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $downloads = $order->get_downloadable_items();

        if ( ! $downloads) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        foreach ($downloads as $download) {
            $product = wc_get_product($download['product_id']);

            $download_url = $product->get_file_download_path($download['download_id']);
            $label = ac_helper()->html->link($download_url, $download['download_name'] ?: $download['product_name']);

            $values[] = ac_helper()->html->tooltip($label, $this->get_description($download));
        }

        return $value->with_value(implode(', ', $values));
    }

    private function get_description($download)
    {
        $product = wc_get_product($download['product_id']);

        if ( ! $product) {
            return $download['product_id'];
        }

        $description = [
            wc_get_filename_from_url($product->get_file_download_path($download['download_id'])),
        ];

        if ( ! empty($download['downloads_remaining'])) {
            $description[] = __('Downloads remaining', 'woocommerce') . ': ' . $download['downloads_remaining'];
        }

        if ( ! empty($download['access_expires'])) {
            /* @var WC_DateTime $date */
            $date = $download['access_expires'];

            if ($date->getTimestamp() > time()) {
                $description[] = __('Access expires', 'woocommerce') . ': ' . human_time_diff($date->getTimestamp());
            }
        }

        return implode('<br/>', $description);
    }

}