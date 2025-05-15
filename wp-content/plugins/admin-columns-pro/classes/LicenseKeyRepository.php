<?php

namespace ACP;

use AC\Storage\KeyValueFactory;
use AC\Storage\KeyValuePair;
use ACP\Type\Activation\Key;
use ACP\Type\LicenseKey;

class LicenseKeyRepository
{

    /**
     * @var KeyValuePair
     */
    private $storage;

    public function __construct(KeyValueFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('acp_subscription_key');
    }

    public function find(): ?LicenseKey
    {
        $key = defined('ACP_LICENCE') && ACP_LICENCE
            ? ACP_LICENCE
            : $this->storage->get();

        if ( ! Key::is_valid($key)) {
            return null;
        }

        $source = $this->is_defined()
            ? LicenseKey::SOURCE_CODE
            : LicenseKey::SOURCE_DATABASE;

        return new LicenseKey($key, $source);
    }

    private function is_defined(): bool
    {
        return defined('ACP_LICENCE') && ACP_LICENCE;
    }

    public function delete(): bool
    {
        return $this->storage->delete();
    }

}