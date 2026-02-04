<?php

declare(strict_types=1);

namespace ACA\Types\Editing\Storage;

use ACP\Editing\Storage;
use Toolset_Relationship_Definition;

abstract class Relationship implements Storage
{

    /**
     * @var string
     */
    protected $relationship;

    abstract protected function connect_post(int $source_id, int $connect_id);

    abstract protected function disconnect_post(int $source_id, int $connect_id);

    abstract protected function get_relation_type(): string;

    public function __construct(Toolset_Relationship_Definition $relationship)
    {
        $this->relationship = $relationship;
    }

    private function get_ids(int $id): array
    {
        return toolset_get_related_posts(
            $id,
            $this->relationship->get_slug(),
            $this->get_relation_type()
        );
    }

    public function get(int $id): array
    {
        $values = [];

        foreach ($this->get_ids($id) as $post_id) {
            $values[$post_id] = (string)get_post_field('post_title', (int)$post_id);
        }

        return $values;
    }

    public function update(int $id, $data): bool
    {
        $old_ids = $this->get_ids($id);

        if ( ! $data) {
            $data = [];
        }

        foreach ($old_ids as $post_id) {
            if ( ! in_array($post_id, $data)) {
                $this->disconnect_post($id, (int)$post_id);
            }
        }

        foreach ($data as $post_id) {
            if (in_array($post_id, $old_ids)) {
                continue;
            }

            $this->connect_post($id, (int)$post_id);
        }

        return true;
    }

}