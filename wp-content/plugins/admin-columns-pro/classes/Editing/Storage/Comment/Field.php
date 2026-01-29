<?php

namespace ACP\Editing\Storage\Comment;

use ACP\Editing\Storage;
use RuntimeException;

class Field implements Storage
{

    private $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function get(int $id)
    {
        $comment = get_comment($id);

        return property_exists($comment, $this->field)
            ? $comment->{$this->field}
            : null;
    }

    public function update(int $id, $data): bool
    {
        $args = [
            'comment_ID' => $id,
            $this->field => $data,
        ];

        $result = wp_update_comment($args);

        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }

        return is_int($result) && $result > 0;
    }

}