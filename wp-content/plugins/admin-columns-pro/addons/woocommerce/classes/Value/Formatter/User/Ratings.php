<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Ratings implements Formatter
{

    private bool $average;

    public function __construct(bool $average)
    {
        $this->average = $average;
    }

    public function format(Value $value)
    {
        global $wpdb;

        $af = $this->average ? 'AVG' : 'COUNT';

        $sql = "
			SELECT {$af}(cm.meta_value)
			FROM {$wpdb->comments} AS c
			INNER JOIN {$wpdb->posts} AS p 
				ON c.comment_post_ID = p.ID 
				AND p.post_type = 'product'
			INNER JOIN {$wpdb->commentmeta} AS cm 
				ON cm.comment_id = c.comment_ID
			WHERE c.user_id = %d
			AND c.comment_approved = 1
			AND cm.meta_key = 'rating'
		";

        $stmt = $wpdb->prepare($sql, [$value->get_id()]);
        $count = $wpdb->get_var($stmt);

        if ( ! $count) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ($this->average) {
            $count = round((float)$count, 3);
        }

        return $value->with_value($count);
    }

}