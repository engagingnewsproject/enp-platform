<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Collection;
use AC\ListScreen;
use ACP\ConditionalFormat\Entity\Rules;

final class RulesCollection extends Collection
{

    public function __construct(array $data = [])
    {
        foreach ($data as $rule) {
            $this->add($rule);
        }
    }

    public function add(Rules $rules): void
    {
        $this->data[] = $rules;
    }

    public function current(): Rules
    {
        return current($this->data);
    }

    public function first(): ?Rules
    {
        return parent::first();
    }

    public function merge(RulesCollection $collection): self
    {
        return new self(
            array_merge($this->data, iterator_to_array($collection))
        );
    }

    /**
     * Filter out columns
     */
    public function with_existing_columns(ListScreen $list_screen): self
    {
        $data = [];

        /**
         * @var Rules $rules
         */
        foreach ($this->data as $rules) {
            $data[] = $rules->with_existing_columns($list_screen);
        }

        return new self($data);
    }

}