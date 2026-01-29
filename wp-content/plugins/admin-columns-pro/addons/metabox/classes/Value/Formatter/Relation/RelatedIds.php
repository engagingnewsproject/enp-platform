<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\Relation;

use AC;
use AC\Type\Value;
use ACA\MetaBox\Entity\Relation;

class RelatedIds implements AC\Formatter
{

    private $relation;

    public function __construct(Relation $relation)
    {
        $this->relation = $relation;
    }

    public function format(Value $value)
    {
        $ids = $this->relation->get_related_ids($value->get_id());

        if (empty($ids)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return AC\Type\ValueCollection::from_ids($value->get_id(), $ids);
    }

}