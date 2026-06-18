<?php

namespace ACP\Access;

use AC\Storage\OptionData;
use AC\Storage\OptionDataFactory;
use ACP\Type\Activation\Key;

class ActivationKeyStorage
{

    private OptionData $storage;

    private ?string $data = null;

    public function __construct(OptionDataFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('acp_activation_key');
    }

    public function find(): ?Key
    {
        $key = $this->get();

        return Key::is_valid($key)
            ? new Key($key)
            : null;
    }

    private function get(): string
    {
        if (null === $this->data) {
            $this->data = (string)$this->storage->get();
        }

        return $this->data;
    }

    private function flush_cache(): void
    {
        $this->data = null;
    }

    public function save(Key $key): void
    {
        $this->storage->save($key->get_token());

        $this->flush_cache();
    }

    public function delete(): void
    {
        $this->storage->delete();

        $this->flush_cache();
    }

}