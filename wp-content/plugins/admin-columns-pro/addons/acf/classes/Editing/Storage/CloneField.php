<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\Storage;

use ACA\ACF\Storage\CloneFieldStorage;
use ACP\Editing\Storage;

final class CloneField implements Storage
{

    private string $clone_hash;

    private string $field_hash;

    private string $parent_key;

    private CloneFieldStorage $storage;

    public function __construct(
        string $clone_hash,
        string $field_hash,
        string $parent_key,
        CloneFieldStorage $storage
    ) {
        $this->clone_hash = $clone_hash;
        $this->field_hash = $field_hash;
        $this->parent_key = $parent_key;
        $this->storage = $storage;
    }

    public function get(int $id)
    {
        return $this->storage->get($id, $this->parent_key);
    }

    public function update(int $id, $data): bool
    {
        return $this->storage->update($id, $this->field_hash, $this->clone_hash, $data);
    }

}