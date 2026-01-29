<?php

declare(strict_types=1);

namespace ACA\MetaBox\Column;

use AC\Column\Context;
use AC\Setting\Config;
use ACA\MetaBox\Entity;

class RelationContext extends Context
{

    private Entity\Relation $relation;

    public function __construct(Config $config, string $label, Entity\Relation $relation)
    {
        parent::__construct($config, $label);

        $this->relation = $relation;
    }

    public function get_relation(): Entity\Relation
    {
        return $this->relation;
    }

}