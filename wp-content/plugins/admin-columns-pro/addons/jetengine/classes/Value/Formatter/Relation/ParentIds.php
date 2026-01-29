<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter\Relation;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use Jet_Engine\Relations\Relation as JetEngineRelation;

final class ParentIds implements Formatter
{

    private $relation;

    public function __construct(JetEngineRelation $relation)
    {
        $this->relation = $relation;
    }

    public function format(Value $value)
    {
        $ids = wp_list_pluck($this->relation->get_parents($value->get_id()), 'parent_object_id');

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}