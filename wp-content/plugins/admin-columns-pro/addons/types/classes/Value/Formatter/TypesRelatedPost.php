<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class TypesRelatedPost implements Formatter
{

    private $relationship;

    private $type;

    public function __construct(string $relationship, string $type)
    {
        $this->relationship = $relationship;
        $this->type = $type;
    }

    public function format(Value $value)
    {
        $ids = toolset_get_related_posts(
            $value->get_id(),
            $this->relationship,
            $this->type
        );

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }
}