<?php

declare(strict_types=1);

namespace ACA\JetEngine\Column;

use AC\Column\Context;
use AC\Setting\Config;
use Jet_Engine;

class RelationContext extends Context
{

    private Jet_Engine\Relations\Relation $relation;

    public function __construct(Config $config, string $label, Jet_Engine\Relations\Relation $relation)
    {
        parent::__construct($config, $label);

        $this->relation = $relation;
    }

    public function get_relation(): Jet_Engine\Relations\Relation
    {
        return $this->relation;
    }

}