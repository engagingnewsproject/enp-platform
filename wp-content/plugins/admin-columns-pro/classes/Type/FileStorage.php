<?php

declare(strict_types=1);

namespace ACP\Type;

class FileStorage
{

    private bool $enabled;

    private ?string $directory;

    public function __construct(bool $enabled, string $directory)
    {
        $this->enabled = $enabled;
        $this->directory = $directory;
    }

    public function is_enabled(): bool
    {
        return $this->enabled;
    }

    public function get_directory(): string
    {
        return $this->directory;
    }

}