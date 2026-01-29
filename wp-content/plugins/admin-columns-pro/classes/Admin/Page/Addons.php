<?php

namespace ACP\Admin\Page;

use AC;

class Addons extends AC\Admin\Page\Addons
{

    protected function is_pro(): bool
    {
        return true;
    }
}