<?php

declare(strict_types=1);

namespace ACA\RankMath\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\MetaType;
use AC\Type\Value;

class HasRobotKey implements Formatter
{

    private const META_KEY = 'rank_math_robots';

    private string $sub_key;

    private MetaType $meta_type;

    public function __construct(string $sub_key, MetaType $meta_type)
    {
        $this->sub_key = $sub_key;
        $this->meta_type = $meta_type;
    }

    public function format(Value $value)
    {
        $meta = get_metadata((string)$this->meta_type, $value->get_id(), self::META_KEY, true);

        if ( ! is_array($meta)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(in_array($this->sub_key, $meta, true));
    }

}