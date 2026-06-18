<?php

declare(strict_types=1);

namespace ACA\MLA\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Asset\Script;
use AC\ListScreen;
use AC\Registerable;

class TableScreen implements Registerable
{

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'table_scripts'], 11);
    }

    public function table_scripts(ListScreen $list_screen)
    {
        if ( ! $list_screen->get_table_screen() instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return;
        }

        $script = new Script('aca-mla-table', $this->location->with_suffix('assets/js/table.js'));
        $script->enqueue();
    }

}