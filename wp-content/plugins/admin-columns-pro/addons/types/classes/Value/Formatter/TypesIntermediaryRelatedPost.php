<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\PostTypeSlug;
use AC\Type\Value;
use AC\Type\ValueCollection;

class TypesIntermediaryRelatedPost implements Formatter
{

    private string $type;

    private PostTypeSlug $post_type;

    public function __construct(PostTypeSlug $post_type, string $type)
    {
        $this->type = $type;
        $this->post_type = $post_type;
    }

    public function format(Value $value)
    {
        $ids = toolset_get_related_posts(
            $value->get_id(),
            (string)$this->post_type,
            ['query_by_role' => 'intermediary', 'role_to_return' => $this->type, 'limit' => -1]
        );

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }
}