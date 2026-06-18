<?php

declare(strict_types=1);

namespace ACP\Exception;

use RuntimeException;

final class ApiUnreachableException extends RuntimeException
{

    private int $response_code;

    public function __construct(string $message, int $response_code)
    {
        parent::__construct($message);

        $this->response_code = $response_code;
    }

    public function get_response_code(): int
    {
        return $this->response_code;
    }

    public static function from_response_code(int $response_code): self
    {
        return new self('Admin Columns Server is unreachable.', $response_code);
    }

}