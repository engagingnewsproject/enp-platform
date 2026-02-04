<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class PostReferenceId implements Formatter
{

    private string $field_slug;

    public function __construct(string $field_slug)
    {
        $this->field_slug = $field_slug;
    }

    public function format(Value $value)
    {
        $results = toolset_get_related_posts(
            $value->get_id(),
            $this->field_slug,
            [
                'query_by_role' => 'child',
            ]
        );

        $values = new ValueCollection($value->get_id());

        foreach ($results as $post_id) {
            $values->add(new Value($post_id));
        }

        return $values;
    }
}