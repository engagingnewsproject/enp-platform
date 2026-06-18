<?php

namespace ACP\Editing\Storage;

use AC\MetaType;
use ACP\Editing\Storage;
use RuntimeException;

class Meta implements Storage
{

    protected string $meta_key;

    private MetaType $meta_type;

    public function __construct(string $meta_key, MetaType $meta_type)
    {
        $this->meta_key = $meta_key;
        $this->meta_type = $meta_type;
    }

    public function get(int $id)
    {
        return get_metadata($this->meta_type->get(), $id, $this->meta_key, true);
    }

    public function update(int $id, $data): bool
    {
        if ('' === $this->meta_key) {
            throw new RuntimeException('Failed to update metadata. Custom field key is missing.');
        }

        $result = update_metadata($this->meta_type->get(), $id, $this->meta_key, $data);

        if (false === $result) {
            throw new RuntimeException('Failed to update metadata.');
        }

        return true;
    }

}