<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class ReviewCount implements Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $sql = "
			SELECT COUNT( comment_ID )
			FROM {$wpdb->comments} AS c
			INNER JOIN {$wpdb->posts} AS p ON c.comment_post_ID = p.ID AND p.post_type = 'product'
			WHERE c.user_id = %d
			AND c.comment_approved = 1
		";

        $stmt = $wpdb->prepare($sql, [$value->get_id()]);

        return $value->with_value((int)$wpdb->get_var($stmt));
    }

}