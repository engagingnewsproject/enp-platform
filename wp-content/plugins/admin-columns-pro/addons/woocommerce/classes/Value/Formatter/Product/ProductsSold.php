<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class ProductsSold implements Formatter
{

    private array $statuses;

    private ?int $number_of_days;

    public function __construct(array $statuses = ['wc-completed'], ?int $number_of_days = null)
    {
        $this->statuses = $statuses;
        $this->number_of_days = $number_of_days;
    }

    public function format(Value $value)
    {
        $sum = (int)$this->get_total_sold((int)$value->get_id());

        if ($sum < 1) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($sum);
    }

    public function get_total_sold(int $product_id): ?string
    {
        global $wpdb;

        $status = apply_filters('ac/wc/column/product/sales/statuses', $this->statuses);
        $status_statement = '';

        if ( ! empty($status)) {
            $status_statement = sprintf(
                "AND wco.status IN ('%s')",
                implode("','", array_map('esc_sql', $status))
            );
        }

        $date_statement = '';

        if ($this->number_of_days !== null) {
            $start_date = new DateTime();
            $start_date->modify('-' . $this->number_of_days . ' days');

            $date_statement = sprintf(
                "AND wco.date_created_gmt >= '%s'",
                $start_date->format('Y-m-d')
            );
        }

        $sql = $wpdb->prepare(
            "
            SELECT SUM( wcopl.product_qty )
            FROM {$wpdb->prefix}wc_order_product_lookup as wcopl
            JOIN {$wpdb->prefix}wc_orders as wco
                ON wcopl.order_id = wco.ID {$status_statement} {$date_statement}
            WHERE wcopl.product_id = %d OR wcopl.variation_id = %d
        ",
            $product_id,
            $product_id
        );

        return $wpdb->get_var($sql);
    }
}
