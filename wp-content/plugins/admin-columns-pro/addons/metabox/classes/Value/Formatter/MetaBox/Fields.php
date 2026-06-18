<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\MetaBox;

use AC;
use AC\Type\Value;

class Fields implements AC\Formatter
{

    public function format(Value $value)
    {
        $data = get_post_meta($value->get_id(), 'fields', true);

        if (empty($data)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $fields = [];
        foreach ($data as $field) {
            $fields[] = sprintf('%s <small style="color: #999">[%s]</small>', $field['name'], $field['type']);
        }

        return $value->with_value(
            implode('<br>', $fields)
        );
    }

}