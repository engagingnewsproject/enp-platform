<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class OrderTotal implements Formatter
{

    private array $statuses;

    private ?int $number_of_days;

    public function __construct(array $statuses = [], ?int $number_of_days = null)
    {
        $this->statuses = $statuses;
        $this->number_of_days = $number_of_days;
    }

    public function format(Value $value): Value
    {
        $total = $this->get_order_total($value->get_id());

        if ( ! $total) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(round($total, 2));
    }

    private function get_order_total(int $post_id): ?float
    {
        global $wpdb;

        $status_statement = '';

        if ( ! empty($this->statuses)) {
            $status_statement = sprintf(
                "AND wco.status IN ('%s')",
                implode("','", array_map('esc_sql', $this->statuses))
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

        $needs_join = ! empty($this->statuses) || $this->number_of_days !== null;

        if ($needs_join) {
            $sql = $wpdb->prepare(
                "
                SELECT SUM( wcopl.product_net_revenue )
                FROM {$wpdb->prefix}wc_order_product_lookup AS wcopl
                JOIN {$wpdb->prefix}wc_orders AS wco
                    ON wcopl.order_id = wco.ID {$status_statement} {$date_statement}
                WHERE wcopl.product_id = %d
                ",
                $post_id
            );
        } else {
            $sql = $wpdb->prepare(
                "
                SELECT SUM( product_net_revenue )
                FROM {$wpdb->prefix}wc_order_product_lookup
                WHERE product_id = %d
                ",
                $post_id
            );
        }

        $sum = $wpdb->get_var($sql);

        if (null === $sum) {
            return null;
        }

        return (float)$sum;
    }

}
