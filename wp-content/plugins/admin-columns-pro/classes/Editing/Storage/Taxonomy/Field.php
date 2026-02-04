<?php

namespace ACP\Editing\Storage\Taxonomy;

use ACP\Editing\Storage;
use RuntimeException;

class Field implements Storage
{

    protected string $taxonomy;

    protected string $field;

    public function __construct(string $taxonomy, string $field)
    {
        $this->taxonomy = $taxonomy;
        $this->field = $field;
    }

    public function get(int $id)
    {
        return ac_helper()->taxonomy->get_term_field($this->field, $id, $this->taxonomy);
    }

    public function update(int $id, $data): bool
    {
        $result = wp_update_term($id, $this->taxonomy, [
            $this->field => $data,
        ]);

        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }

        return is_int($result) && $result > 0;
    }

}