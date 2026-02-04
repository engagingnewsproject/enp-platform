<?php

namespace ACP\Exception;

use RuntimeException;

class FailedToCreateDirectoryException extends RuntimeException
{

    private string $path;

    public function __construct(string $path, int $code = 0)
    {
        parent::__construct(sprintf('Could not create directory %s.', $path), $code);

        $this->path = $path;
    }

    public function get_path(): string
    {
        return $this->path;
    }

}