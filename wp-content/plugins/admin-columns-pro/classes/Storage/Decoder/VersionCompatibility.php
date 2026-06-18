<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\Plugin\Version;
use ACP\Exception\NonDecodableDataException;

final class VersionCompatibility
{

    public function assert(array $encoded_data, Version $version): void
    {
        if ( ! $this->supports($encoded_data, $version)) {
            throw new NonDecodableDataException($encoded_data);
        }
    }

    public function supports(array $encoded_data, Version $version): bool
    {
        $encoded_version = $encoded_data['version'] ?? null;
  
        if ( ! $encoded_version || ! is_string($encoded_version)) {
            return false;
        }

        return $version->is_lte(new Version($encoded_data['version']));
    }

}