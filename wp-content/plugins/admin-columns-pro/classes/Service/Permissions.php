<?php

namespace ACP\Service;

use AC\Registerable;
use ACP\Access;
use ACP\Access\PermissionsStorage;

class Permissions implements Registerable
{

    private PermissionsStorage $permission_storage;

    private Access\PermissionCheckerFactory $permission_factory;

    public function __construct(PermissionsStorage $permission_storage, Access\PermissionCheckerFactory $permission_factory)
    {
        $this->permission_storage = $permission_storage;
        $this->permission_factory = $permission_factory;
    }

    public function register(): void
    {
        $this->set_permissions();
    }

    public function set_permissions(): void
    {
        if ($this->permission_storage->exists()) {
            return;
        }

        $this->permission_factory
            ->create()
            ->apply();
    }

}