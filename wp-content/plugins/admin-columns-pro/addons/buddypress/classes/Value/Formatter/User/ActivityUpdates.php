<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class ActivityUpdates implements Formatter
{

    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function format(Value $value): Value
    {
        global $wpdb, $bp;

        $sql = $wpdb->prepare(
            "SELECT COUNT(user_id) FROM {$bp->activity->table_name} WHERE user_id = %d",
            (int)$value->get_id()
        );

        if ($this->type) {
            $sql .= $wpdb->prepare(' AND type = %s', $this->type);
        }

        return $value->with_value(
            $wpdb->get_var($sql)
        );
    }

}