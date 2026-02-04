<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\Storage;

use ACA\ACF\Storage\GroupFieldStorage;
use ACP;

final class GroupField implements ACP\Editing\Storage
{

    private string $group_key;

    private string $sub_key;

    private string $parent_key;

    private GroupFieldStorage $storage;

    public function __construct(
        string $group_key,
        string $sub_key,
        string $parent_key,
        GroupFieldStorage $storage
    ) {
        $this->group_key = $group_key;
        $this->sub_key = $sub_key;
        $this->parent_key = $parent_key;
        $this->storage = $storage;
    }

    public function get(int $id)
    {
        return $this->storage->get($id, $this->parent_key);
    }

    public function update(int $id, $data): bool
    {
        return $this->storage->update($id, $this->group_key, $this->sub_key, $data);
    }

}