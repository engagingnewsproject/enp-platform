<?php

namespace ACP\Query\Bindings;

use ACP\Query\Bindings;

class User extends Bindings
{

    protected array $tax_query = [];

    public function get_tax_query(): array
    {
        return $this->tax_query;
    }

    public function tax_query(array $args): self
    {
        $this->tax_query = $args;

        return $this;
    }

}