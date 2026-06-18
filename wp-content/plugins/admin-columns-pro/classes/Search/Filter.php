<?php

namespace ACP\Search;

abstract class Filter
{

    protected string $name;

    protected Comparison $comparison;

    protected string $label;

    public function __construct(string $name, Comparison $comparison, string $label)
    {
        $this->name = $name;
        $this->comparison = $comparison;
        $this->label = $label;
    }

    abstract public function __invoke();

}