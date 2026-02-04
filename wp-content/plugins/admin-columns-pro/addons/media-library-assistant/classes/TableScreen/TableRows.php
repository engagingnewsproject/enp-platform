<?php

declare(strict_types=1);

namespace ACA\MLA\TableScreen;

use AC;
use AC\ThirdParty\MediaLibraryAssistant\WpListTableFactory;

class TableRows extends AC\TableScreen\TableRows
{

    public function register(): void
    {
        add_action('mla_list_table_prepare_items', [$this, 'handle_request']);

        // Triggers hook above
        (new WpListTableFactory())->create();
    }

    public function handle_request()
    {
        parent::handle(new AC\Request());
    }

}