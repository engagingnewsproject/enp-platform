<?php

declare(strict_types=1);

namespace ACA\Types\Column;

use AC\Column\Context;
use AC\Setting\Config;

class RelationshipContext extends Context
{

    private array $relation;

    public function __construct(Config $config, string $label, array $relation)
    {
        parent::__construct($config, $label);

        $this->relation = $relation;
    }

    public function get_relation(): array
    {
        return $this->relation;
    }
}