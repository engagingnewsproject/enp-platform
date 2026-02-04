<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class AvgOrderInterval implements Formatter
{

    private int $number_of_days;

    public function __construct(int $number_of_days)
    {
        $this->number_of_days = $number_of_days;
    }

    public function format(Value $value)
    {
        global $wpdb;

        $start_date = new DateTime();
        $start_date->modify('-' . $this->number_of_days . 'days');
        $post_id = $value->get_id();

        $sql = "SELECT COUNT(*) as count
            FROM {$wpdb->prefix}wc_orders as o
            JOIN {$wpdb->prefix}wc_order_product_lookup as op
                ON o.id = op.order_id AND op.product_id = {$post_id}
            WHERE 
                o.date_created_gmt >= {$start_date->format('Y-m-d')}
                AND o.type = 'shop_order'
                AND o.status = 'wc-completed'
            ";

        $count = (int)$wpdb->get_var($sql);

        if ( ! $count) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $days = round($this->number_of_days / $count, 3);

        return $value->with_value((int)$days);
    }

}