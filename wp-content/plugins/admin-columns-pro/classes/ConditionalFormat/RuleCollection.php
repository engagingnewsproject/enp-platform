<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Collection;
use ACP\ConditionalFormat\Type\Rule;

final class RuleCollection extends Collection
{

    public function __construct(array $data = [])
    {
        foreach ($data as $rule) {
            $this->add($rule);
        }
    }

    public function add(Rule $rule): void
    {
        $this->data[] = $rule;
    }

    public function current(): Rule
    {
        return current($this->data);
    }

    public function first(): ?Rule
    {
        return parent::first();
    }

}