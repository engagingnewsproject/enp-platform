<?php

declare(strict_types=1);

namespace ACP\Sorting\Service;

use AC\ListScreen;
use AC\Registerable;
use ACP\Sorting\Type\SortType;
use ACP\Sorting\UserPreference;

class SaveSortingPreference implements Registerable
{

    private ListScreen $list_screen;

    public function __construct(ListScreen $list_screen)
    {
        $this->list_screen = $list_screen;
    }

    public function register(): void
    {
        add_action('shutdown', [$this, 'save']);
    }

    private function user_preference(): UserPreference\SortType
    {
        return UserPreference\SortType::create($this->list_screen);
    }

    public function save(): void
    {
        $persist = (bool)apply_filters(
            'ac/sorting/remember_last_sorting_preference',
            true,
            $this->list_screen->get_table_screen()
        );

        if ( ! $persist) {
            return;
        }

        $sort_type = SortType::create_by_request_globals();

        if ($sort_type) {
            $this->user_preference()->save($sort_type);
        }
    }

}