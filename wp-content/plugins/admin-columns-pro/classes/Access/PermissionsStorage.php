<?php

namespace ACP\Access;

use AC\Storage\OptionData;
use AC\Storage\OptionDataFactory;

final class PermissionsStorage
{

    private OptionData $storage;

    private $data;

    public function __construct(OptionDataFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('_acp_access_permissions');
    }

    public function retrieve(): Permissions
    {
        return new Permissions($this->get() ?: []);
    }

    private function get()
    {
        if (null === $this->data) {
            $this->data = $this->storage->get();
        }

        return $this->data;
    }

    public function exists(): bool
    {
        return false !== $this->get();
    }

    public function save(Permissions $permissions): void
    {
        $this->storage->save($permissions->to_array());

        // flush cache
        $this->data = null;
    }

}