<?php

declare(strict_types=1);

namespace ACP\Table;

use AC\Asset\Style;
use AC\ColumnSize;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Registerable;
use AC\Settings\GeneralOption;
use ACP\AdminColumnsPro;
use ACP\Asset\Script\Table;

class Scripts implements Registerable
{

    private $location;

    private $user_storage;

    private $list_storage;

    private $storage;

    private GeneralOption $option_storage;

    public function __construct(
        AdminColumnsPro $plugin,
        ColumnSize\UserStorage $user_storage,
        ColumnSize\ListStorage $list_storage,
        Storage $storage,
        GeneralOption $option_storage
    ) {
        $this->location = $plugin->get_location();
        $this->user_storage = $user_storage;
        $this->list_storage = $list_storage;
        $this->storage = $storage;
        $this->option_storage = $option_storage;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'scripts']);
    }

    public function scripts(ListScreen $list_screen): void
    {
        $assets = [
            new Style('acp-table', $this->location->with_suffix('assets/core/css/table.css')),
            new Table(
                $this->location->with_suffix('assets/core/js/table.js'),
                $list_screen,
                $this->user_storage,
                $this->list_storage,
                $this->storage,
                $this->option_storage
            ),
        ];

        foreach ($assets as $asset) {
            $asset->enqueue();
        }
    }

}