<?php

declare(strict_types=1);

namespace ACP\TableScreen\RelatedRepository;

use AC\PostTypeRepository;
use AC\TableScreenFactory;
use AC\Type\TableId;
use AC\Type\TableIdCollection;
use ACP\TableScreen\RelatedRepository;

class Post implements RelatedRepository
{

    use TableTrait;

    private PostTypeRepository $post_type_repository;

    public function __construct(
        PostTypeRepository $post_type_repository,
        TableScreenFactory\Aggregate $table_screen_factory
    ) {
        $this->post_type_repository = $post_type_repository;

        $this->set_table_screen_factory($table_screen_factory);
    }

    protected function get_keys(): TableIdCollection
    {
        $keys = new TableIdCollection();

        foreach ($this->post_type_repository->find_all() as $post_type) {
            $keys->add(new TableId($post_type));
        }

        return $keys;
    }

}