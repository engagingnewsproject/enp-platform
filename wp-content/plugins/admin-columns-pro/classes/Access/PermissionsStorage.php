<?php

namespace ACP\Access;

use AC\Storage\KeyValueFactory;
use AC\Storage\KeyValuePair;

final class PermissionsStorage
{

    /**
     * @var KeyValuePair
     */
    private $storage;

    public function __construct(KeyValueFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('_acp_access_permissions');
    }

    public function retrieve(): Permissions
    {
        return new Permissions($this->storage->get() ?: []);
    }

    public function exists(): bool
    {
        return $this->storage->exists();
    }

    public function save(Permissions $permissions): void
    {
        $this->storage->save($permissions->to_array());
    }

}