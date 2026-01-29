<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

trait QueryArgsTrait
{

    public function get_query_args(): array
    {
        return isset($this->settings['query_args']) ? (array)$this->settings['query_args'] : [];
    }

}