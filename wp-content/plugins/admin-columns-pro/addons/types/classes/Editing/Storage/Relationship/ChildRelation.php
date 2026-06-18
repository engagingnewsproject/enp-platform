<?php

declare(strict_types=1);

namespace ACA\Types\Editing\Storage\Relationship;

use ACA\Types;

class ChildRelation extends Types\Editing\Storage\Relationship
{

    protected function get_relation_type(): string
    {
        return 'child';
    }

    protected function connect_post(int $source_id, int $connect_id)
    {
        toolset_connect_posts($this->relationship->get_slug(), $connect_id, $source_id);
    }

    protected function disconnect_post(int $source_id, int $connect_id)
    {
        toolset_disconnect_posts($this->relationship->get_slug(), $connect_id, $source_id);
    }

}