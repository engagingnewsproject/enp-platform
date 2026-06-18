<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\TableScreen;

use AC;
use AC\MetaType;
use AC\PostType;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use AC\Type\PostTypeSlug;

class Template extends TableScreen implements ListTable, PostType, TableScreen\MetaType, TableScreen\TotalItems
{

    use AC\ListTable\TotalItemsTrait;

    public function get_post_type(): PostTypeSlug
    {
        return new PostTypeSlug('fl-builder-template');
    }

    public function list_table(): AC\ListTable
    {
        return AC\ListTableFactory::create_post($this->screen_id);
    }

    public function get_meta_type(): MetaType
    {
        return new MetaType(MetaType::POST);
    }

}