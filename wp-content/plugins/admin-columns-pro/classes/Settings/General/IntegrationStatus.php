<?php

declare(strict_types=1);

namespace ACP\Settings\General;

use AC\Storage\GeneralOption;

class IntegrationStatus
{

    private $storage;

    public function __construct(GeneralOption $storage)
    {
        $this->storage = $storage;
    }

    public function set_active(string $slug): void
    {
        $this->storage->remove('integration_' . $slug);
    }

    public function set_inactive(string $slug): void
    {
        $this->storage->save('integration_' . $slug, 'inactive');
    }

    public function is_active(string $slug): bool
    {
        return in_array(
            $this->storage->find('integration_' . $slug),
            ['active', null],
            true
        );
    }

}