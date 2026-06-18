<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Type;

use InvalidArgumentException;

final class Key
{

    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;

        if ( ! self::validate($key)) {
            throw new InvalidArgumentException('Invalid key.');
        }
    }

    public static function validate(string $key): bool
    {
        return '' !== $key;
    }

    public function equals(Key $key): bool
    {
        return (string)$key === $this->key;
    }

    public function __toString(): string
    {
        return $this->key;
    }

}