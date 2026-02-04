<?php

declare(strict_types=1);

namespace ACA\RankMath\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\MetaType;
use AC\Type\Value;

class Robots implements Formatter
{

    private const META_KEY = 'rank_math_robots';

    private MetaType $meta_type;

    public function __construct(MetaType $meta_type)
    {
        $this->meta_type = $meta_type;
    }

    private function get_label(string $key): string
    {
        $mapping = [
            'index'        => __('Index', 'rank-math'),
            'noarchive'    => __('No Archive', 'rank-math'),
            'nofollow'     => __('No Follow', 'rank-math'),
            'noimageindex' => __('No Image Index', 'rank-math'),
            'noindex'      => __('No Index', 'rank-math'),
            'nosnippet'    => __('No Snippet', 'rank-math'),
        ];

        return array_key_exists($key, $mapping) ? $mapping[$key] : $key;
    }

    public function format(Value $value)
    {
        $meta = get_metadata((string)$this->meta_type, $value->get_id(), self::META_KEY, true);

        if ( ! is_array($meta)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $labels = [];

        foreach ($meta as $key) {
            $labels[] = $this->get_label($key);
        }

        return $value->with_value(implode(', ', $labels));
    }

}