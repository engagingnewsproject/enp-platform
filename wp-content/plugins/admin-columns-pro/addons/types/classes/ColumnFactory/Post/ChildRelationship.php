<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Post;

use AC\Setting\Config;
use ACA\Types\Editing;
use ACA\Types\Search;
use ACP;

class ChildRelationship extends Relationship
{

    protected function get_related_post_type(): string
    {
        return $this->relationship->get_parent_type()->get_types()[0];
    }

    protected function get_relation_type(): string
    {
        return 'child';
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Relationship(
            new Editing\Storage\Relationship\ChildRelation($this->relationship),
            $this->get_related_post_type()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Post\Relationship(
            $this->relationship->get_slug(),
            $this->get_related_post_type(),
            'parent',
            'child'
        );
    }

}