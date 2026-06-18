<?php

namespace ACP\Access;

final class PermissionCheckerFactory
{

    private PermissionsStorage $permissions_storage;

    public function __construct(PermissionsStorage $permissions_storage)
    {
        $this->permissions_storage = $permissions_storage;
    }

    public function create(): PermissionChecker
    {
        return new PermissionChecker($this->permissions_storage);
    }

}
