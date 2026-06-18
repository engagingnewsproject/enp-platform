<?php

declare(strict_types=1);

namespace ACA\Types\Editing\Storage\Relationship;

use ACA\Types;

class ParentRelation extends Types\Editing\Storage\Relationship
{

    protected function get_relation_type(): string
    {
        return 'parent';
    }

    protected function connect_post($source_id, $connect_id)
    {
        toolset_connect_posts($this->relationship->get_slug(), $source_id, $connect_id);
    }

    protected function disconnect_post($source_id, $connect_id)
    {
        toolset_disconnect_posts($this->relationship->get_slug(), $source_id, $connect_id);
    }
}