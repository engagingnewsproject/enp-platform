<?php

declare(strict_types=1);

namespace ACP\Storage;

use AC\Plugin\Version;
use ACP\Storage\Decoder\VersionCompatibility;

abstract class DecoderFactory
{

    protected VersionCompatibility $version_compatibility;

    public function __construct(
        Decoder\VersionCompatibility $version_compatibility
    ) {
        $this->version_compatibility = $version_compatibility;
    }

    public function create(array $encoded_data): Decoder
    {
        $this->version_compatibility->assert($encoded_data, $this->get_version());

        return $this->create_decoder($encoded_data);
    }

    public function supports(array $encoded_data): bool
    {
        return $this->version_compatibility->supports($encoded_data, $this->get_version());
    }

    abstract protected function create_decoder(array $encoded_data): Decoder;

    abstract protected function get_version(): Version;

}