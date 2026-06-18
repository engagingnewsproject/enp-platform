<?php

declare(strict_types=1);

namespace ACP\Storage;

use AC\ListScreen;
use AC\Type\ListScreenId;
use AC\Type\TableId;

interface Decoder
{

    public function has_list_screen(): bool;

    public function get_list_screen(): ListScreen;

    public function get_list_screen_id(): ListScreenId;

    public function get_table_id(): TableId;

}