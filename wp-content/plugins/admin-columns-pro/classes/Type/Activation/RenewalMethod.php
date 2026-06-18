<?php

namespace ACP\Type\Activation;

use LogicException;

final class RenewalMethod
{

    private const METHOD_AUTO = 'auto';
    private const METHOD_MANUAL = 'manual';

    private string $method;

    public function __construct(string $method)
    {
        if ( ! self::is_valid($method)) {
            throw new LogicException('Invalid renewal method.');
        }

        $this->method = $method;
    }

    public function is_auto_renewal(): bool
    {
        return self::METHOD_AUTO === $this->method;
    }

    public function is_manual_renewal(): bool
    {
        return self::METHOD_MANUAL === $this->method;
    }

    public function get_value(): string
    {
        return $this->method;
    }

    public static function is_valid(string $method): bool
    {
        return in_array($method, [self::METHOD_AUTO, self::METHOD_MANUAL], true);
    }

}