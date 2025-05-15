<?php

namespace ACP\Access;

use AC\Storage\KeyValueFactory;
use AC\Storage\KeyValuePair;
use ACP\Type\Activation\Key;

class ActivationKeyStorage
{

    /**
     * @var KeyValuePair
     */
    private $storage;

    public function __construct(KeyValueFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('acp_activation_key');
    }

    public function find(): ?Key
    {
        $key = $this->get();

        if ( ! Key::is_valid($key)) {
            return null;
        }

        return new Key($key);
    }

    private function get()
    {
        return $this->storage->get();
    }

    public function save(Key $key): bool
    {
        return $this->storage->save($key->get_token());
    }

    public function delete(): bool
    {
        return $this->storage->delete();
    }

}