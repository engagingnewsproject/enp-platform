<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\Storage;

use ACA\ACF\Storage\FieldStorage;
use ACP;

class Field implements ACP\Editing\Storage
{

    private string $key;

    private FieldStorage $storage;

    public function __construct(string $key, FieldStorage $storage)
    {
        $this->key = $key;
        $this->storage = $storage;
    }

    public function get(int $id)
    {
        return $this->storage->get($id, $this->key) ?: false;
    }

    public function update(int $id, $data): bool
    {
        // Null is not allowed
        return $this->storage->update(
            $id,
            $this->key,
            is_null($data)
                ? false
                : $data
        );
    }

}