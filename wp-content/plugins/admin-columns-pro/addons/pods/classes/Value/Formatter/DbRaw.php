<?php

declare(strict_types=1);

namespace ACA\Pods\Value\Formatter;

use AC;
use AC\Type\Value;

class DbRaw implements AC\Formatter
{

    private string $meta_key;

    private AC\MetaType $meta_type;

    public function __construct(string $meta_key, AC\MetaType $meta_type)
    {
        $this->meta_key = $meta_key;
        $this->meta_type = $meta_type;
    }

    public function format(Value $value)
    {
        global $wpdb;

        $id = $value->get_id();

        switch ($this->meta_type->get()) {
            case AC\MetaType::POST:
                $sql = $wpdb->prepare(
                    "
					SELECT $wpdb->postmeta.meta_value 
					FROM $wpdb->postmeta 
					WHERE $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.post_id = %d
				",
                    $this->meta_key,
                    $id
                );

                break;
            case AC\MetaType::USER:
                $sql = $wpdb->prepare(
                    "
					SELECT $wpdb->usermeta.meta_value 
					FROM $wpdb->usermeta 
					WHERE $wpdb->usermeta.meta_key = %s 
					AND $wpdb->usermeta.user_id = %d
				",
                    $this->meta_key,
                    $id
                );

                break;
            case AC\MetaType::COMMENT:
                $sql = $wpdb->prepare(
                    "
					SELECT $wpdb->commentmeta.meta_value 
					FROM $wpdb->commentmeta 
					WHERE $wpdb->commentmeta.meta_key = %s 
					AND $wpdb->commentmeta.comment_id = %d
				",
                    $this->meta_key,
                    $id
                );

                break;
            case AC\MetaType::TERM:
                $sql = $wpdb->prepare(
                    "
					SELECT $wpdb->termmeta.meta_value 
					FROM $wpdb->termmeta 
					WHERE $wpdb->termmeta.meta_key = %s 
					AND $wpdb->termmeta.term_id = %d
				",
                    $this->meta_key,
                    $id
                );

                break;
            default :
                $sql = false;
        }

        if ( ! $sql) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($wpdb->get_col($sql));
    }

}