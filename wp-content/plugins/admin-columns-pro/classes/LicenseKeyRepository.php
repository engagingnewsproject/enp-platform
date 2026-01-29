<?php

namespace ACP;

use AC\Storage\OptionData;
use AC\Storage\OptionDataFactory;
use ACP\Type\Activation\Key;
use ACP\Type\LicenseKey;

class LicenseKeyRepository
{

    private OptionData $storage;

    public function __construct(OptionDataFactory $storage_factory)
    {
        $this->storage = $storage_factory->create('acp_subscription_key');
    }

    public function find(): ?LicenseKey
    {
        $key = defined('ACP_LICENCE') && ACP_LICENCE
            ? ACP_LICENCE
            : $this->storage->get();

        if ( ! Key::is_valid((string)$key)) {
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

    public function delete(): void
    {
        $this->storage->delete();
    }

}