<?php

namespace ACP\Editing\Storage\User;

use ACP\Editing\Storage;
use RuntimeException;

class Field implements Storage
{

    private string $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function get(int $id)
    {
        return get_userdata($id)->{$this->field} ?? null;
    }

    public function update(int $id, $data): bool
    {
        $args = [
            $this->field => $data,
            'ID'         => $id,
        ];

        $result = wp_update_user($args);

        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }

        clean_user_cache($id);

        return is_int($result) && $result > 0;
    }

}