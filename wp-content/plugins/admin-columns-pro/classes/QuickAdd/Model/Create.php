<?php

namespace ACP\QuickAdd\Model;

use WP_User;

interface Create
{

    /**
     * @return mixed
     */
    public function create();

    public function has_permission(WP_User $user): bool;

}