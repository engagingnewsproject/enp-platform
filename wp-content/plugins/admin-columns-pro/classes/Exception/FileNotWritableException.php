<?php

declare(strict_types=1);

namespace ACP\Exception;

use RuntimeException;

class FileNotWritableException extends RuntimeException
{

    public static function for_file(string $file): self
    {
        $message = sprintf('%s is not writable.', $file);

        return new self($message);
    }

}