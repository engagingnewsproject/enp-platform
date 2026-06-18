<?php

namespace ACP;

use AC\Storage\OptionData;
use AC\Storage\OptionDataFactory;
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
        $key = defined('ACP_LICENCE') && constant('ACP_LICENCE')
            ? constant('ACP_LICENCE')
            : $this->storage->get();

        if ( ! LicenseKey::is_valid((string)$key)) {
            return null;
        }

        return new LicenseKey((string)$key);
    }

    public function delete(): void
    {
        $this->storage->delete();
    }

}