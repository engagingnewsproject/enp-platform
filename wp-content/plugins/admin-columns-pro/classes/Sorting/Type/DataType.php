<?php

namespace ACP\Sorting\Type;

use LogicException;

class DataType
{

    public const STRING = 'string';
    public const NUMERIC = 'numeric';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const DECIMAL = 'decimal';

    private string $value;

    public function __construct(string $value)
    {
        if ( ! self::is_valid($value)) {
            throw new LogicException('Invalid data type.');
        }

        $this->value = $value;
    }

    public function get_value(): string
    {
        return $this->value;
    }

    public static function create_date(): self
    {
        return new self(self::DATE);
    }

    public static function create_decimal(): self
    {
        return new self(self::DECIMAL);
    }

    public static function create_numeric(): self
    {
        return new self(self::NUMERIC);
    }

    public static function create_date_time(): self
    {
        return new self(self::DATETIME);
    }

    public static function create_string(): self
    {
        return new self(self::STRING);
    }

    public static function is_valid(string $value): bool
    {
        return in_array($value, [self::STRING, self::NUMERIC, self::DATE, self::DATETIME, self::DECIMAL], true);
    }

    public function __toString()
    {
        return $this->value;
    }

}