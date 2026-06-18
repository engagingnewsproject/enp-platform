<?php

declare(strict_types=1);

namespace ACA\Types\Editing\Storage;

use AC\MetaType;
use ACP;

class Repeater implements ACP\Editing\Storage
{

    private string $meta_key;

    private MetaType $meta_type;

    public function __construct(string $meta_key, MetaType $meta_type)
    {
        $this->meta_key = $meta_key;
        $this->meta_type = $meta_type;
    }

    public function get(int $id)
    {
        return get_metadata($this->meta_type->get(), $id, $this->meta_key);
    }

    public function update(int $id, $data): bool
    {
        delete_metadata($this->meta_type->get(), $id, $this->meta_key, null);

        $results = [];

        foreach ($data as $_val) {
            $results[] = add_metadata($this->meta_type->get(), $id, $this->meta_key, $_val);
        }

        return ! in_array(false, $results, true);
    }

}