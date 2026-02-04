<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class Coupons implements Formatter
{

    public function format(Value $value)
    {
        $coupons = $this->get_linked_coupons((int)$value->get_id());

        if (empty($coupons)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        foreach ($coupons as $coupon_id) {
            $coupon = new WC_Coupon($coupon_id);

            if ($coupon->get_id() <= 0) {
                continue;
            }

            $link = get_edit_post_link($coupon->get_id());
            $code = $coupon->get_code();

            $values[] = $link
                ? ac_helper()->html->link($link, $code)
                : $code;
        }

        return $value->with_value(
            implode(', ', $values)
        );
    }

    private function get_linked_coupons(int $id): array
    {
        global $wpdb;

        $sql = "SELECT p.ID 
				FROM $wpdb->posts as p
				INNER JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id
				WHERE post_type = 'shop_coupon'
				AND meta_key = 'product_ids'
				AND FIND_IN_SET( %d, pm.meta_value )";

        $query = $wpdb->prepare($sql, [$id]);

        return $wpdb->get_col($query);
    }

}