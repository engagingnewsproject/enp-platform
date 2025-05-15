<?php

namespace ACA\MetaBox\Editing\Storage;

use ACP;
use MetaBox\CustomTable\Cache;
use MetaBox\CustomTable\Storage;

class CustomTable implements ACP\Editing\Storage
{

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var string
     */
    private $meta_key;

    /**
     * @var string
     */
    private $table;

    public function __construct(Storage $storage, $table, $meta_key)
    {
        $this->storage = $storage;
        $this->meta_key = $meta_key;
        $this->table = $table;
    }

    public function get(int $id)
    {
        $row = Cache::get($id, $this->table);

        $value = isset($row[$this->meta_key]) ? $row[$this->meta_key] : false;

        if ( ! $this->is_serialized($value)) {
            return $value;
        }

        $unserialize = @unserialize($value, ['allowed_classes' => false]);

        return $unserialize !== false ? $unserialize : $value;
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

        $row[$this->meta_key] = $data;

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