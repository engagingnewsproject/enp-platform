<?php

declare(strict_types=1);

namespace ACP\Formatter\Comment;

use AC;
use AC\Type\Value;

class ReplyCount implements AC\Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
			SELECT count(*)
			FROM $wpdb->comments
			WHERE comment_parent = %d
		",
            (int)$value->get_id()
        );

        return $value->with_value((int)$wpdb->get_var($sql));
    }

}