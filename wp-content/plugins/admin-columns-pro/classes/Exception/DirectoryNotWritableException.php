<?php

namespace ACP\Exception;

use RuntimeException;

class DirectoryNotWritableException extends RuntimeException
{

    private string $path;

    public function __construct(string $path, $code = 0)
    {
        parent::__construct(sprintf('Directory with path %s is not writable.', $path), $code);

        $this->path = $path;
    }

    public function get_path(): string
    {
        return $this->path;
    }

}