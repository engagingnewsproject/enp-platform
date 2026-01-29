<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing\Storage;

use AC\MetaType;
use ACP;
use RWMB_Field;

class Field implements ACP\Editing\Storage
{

    protected $meta_key;

    protected $meta_type;

    protected $field_settings;

    protected $single;

    public function __construct(string $meta_key, MetaType $meta_type, array $field_settings, bool $single = true)
    {
        $this->meta_key = $meta_key;
        $this->meta_type = $meta_type;
        $this->field_settings = $field_settings;
        $this->single = $single;
    }

    public function get(int $id)
    {
        return get_metadata((string)$this->meta_type, $id, $this->meta_key, $this->single);
    }

    public function update(int $id, $data): bool
    {
        RWMB_Field::save($data, $this->get($id), $id, $this->field_settings);

        return true;
    }

}