<?php

declare(strict_types=1);

namespace ACP\Exception;

use RuntimeException;

final class FailedToSaveConditionalFormattingException extends RuntimeException
{

    public function __construct(?string $message = null)
    {
        if ($message === null) {
            $message = 'Failed to save rules for conditional formatting.';
        }

        parent::__construct($message);
    }

}