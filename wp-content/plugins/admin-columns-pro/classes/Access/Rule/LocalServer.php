<?php

namespace ACP\Access\Rule;

use ACP;
use ACP\Access\Permissions;
use ACP\Access\Platform;

class LocalServer implements ACP\Access\Rule
{

    public function get_permissions(): Permissions
    {
        if (Platform::is_local()) {
            return new Permissions([Permissions::USAGE]);
        }

        return new Permissions();
    }

}