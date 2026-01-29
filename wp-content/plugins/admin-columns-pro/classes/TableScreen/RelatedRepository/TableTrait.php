<?php

namespace ACP\TableScreen\RelatedRepository;

use AC\Table\TableScreenCollection;
use AC\TableScreenFactory;
use AC\Type\TableId;

trait TableTrait
{

    private $table_screen_factory;

    protected function set_table_screen_factory(TableScreenFactory $table_screen_factory): void
    {
        $this->table_screen_factory = $table_screen_factory;
    }

    public function find_all(TableId $table_id): TableScreenCollection
    {
        $keys = $this->get_keys();

        if ( ! $keys->contains($table_id)) {
            return new TableScreenCollection();
        }

        $table_screens = new TableScreenCollection();

        foreach ($keys as $key) {
            if ($this->table_screen_factory->can_create($key)) {
                $table_screens->add($this->table_screen_factory->create($key));
            }
        }

        return $table_screens;
    }

}