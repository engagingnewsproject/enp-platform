<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\PostType;

use AC;
use AC\Type\Value;

class PostTypeSetting implements AC\Formatter
{

    private $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function format(Value $value)
    {
        $raw_data = json_decode(get_post_field('post_content', $value->get_id()), true);

        if (empty($raw_data)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        if ( ! isset($raw_data[$this->key])) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($raw_data[$this->key]);
    }

}