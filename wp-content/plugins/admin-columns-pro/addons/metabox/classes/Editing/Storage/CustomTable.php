<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing\Storage;

use ACP;
use MetaBox\CustomTable\Cache;
use MetaBox\CustomTable\Storage;

class CustomTable implements ACP\Editing\Storage
{

    private Storage $storage;

    private string $table;

    private string $field_id;

    public function __construct(Storage $storage, string $field_id)
    {
        $this->storage = $storage;
        $this->table = (string)$storage->table;
        $this->field_id = $field_id;
    }

    public function get(int $id)
    {
        $row = Cache::get($id, $this->table);

        $value = $row[$this->field_id] ?? false;

        if ( ! $this->is_serialized($value)) {
            return $value;
        }

        $data = @unserialize($value, ['allowed_classes' => false]);

        return $data !== false ? $data : $value;
    }

    private function is_serialized($value): bool
    {
        if ( ! is_string($value)) {
            return false;
        }
        $value = trim($value);

        if ($value === 'N;') {
            return true;
        }
        if ( ! preg_match('/^([adObis]):/', $value, $matches)) {
            return false;
        }

        return true;
    }

    public function update(int $id, $data): bool
    {
        $row = Cache::get($id, $this->table);

        if (is_array($data)) {
            $data = serialize($data);
        }

        $row[$this->field_id] = $data;

        if ($this->storage->row_exists($id)) {
            $this->storage->update_row($id, $row);
        } else {
            $row['ID'] = $id;
            $this->storage->insert_row($row);
        }

        Cache::set($id, $this->table, $row);

        return true;
    }

}