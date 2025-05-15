<?php

declare(strict_types=1);

namespace ACP\Transient;

use AC\Expirable;
use AC\Storage;
use AC\Storage\SiteOption;

/**
 * This transient will be expired after a specified number of seconds.
 * On a network or network sub-site this will run once for the entire network.
 */
class TimeTransient implements Expirable
{

    protected Storage\Timestamp $storage;

    private int $expiration_seconds;

    public function __construct(string $key, int $expiration_seconds)
    {
        $this->storage = new Storage\Timestamp(new SiteOption($key));
        $this->expiration_seconds = $expiration_seconds;
    }

    public function is_expired(int $timestamp = null): bool
    {
        return $this->storage->is_expired($timestamp);
    }

    public function delete(): void
    {
        $this->storage->delete();
    }

    public function save(): bool
    {
        // Always store timestamp before option data.
        return $this->storage->save(time() + $this->expiration_seconds);
    }
}