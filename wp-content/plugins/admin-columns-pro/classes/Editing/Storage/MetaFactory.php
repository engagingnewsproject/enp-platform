<?php

declare(strict_types=1);

namespace ACP\Editing\Storage;

class MetaFactory
{

    public function create(string $field, $meta_type): Meta
    {
        return new Meta($field, $meta_type);
    }

}