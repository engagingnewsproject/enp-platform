<?php

namespace ACP\Search;

use LogicException;

final class Value
{

    public const INT = 'int';
    public const DECIMAL = 'decimal';
    public const STRING = 'string';
    public const DATE = 'date';

    protected ?string $type;

    protected $value;

    public function __construct($value, ?string $type = null)
    {
        if (null === $type) {
            $type = self::STRING;
        }

        $this->type = $type;
        $this->value = $value;

        $this->validate_type();
    }

    private function validate_type()
    {
        $types = [self::INT, self::DECIMAL, self::STRING, self::DATE];

        if ( ! in_array($this->type, $types, true)) {
            throw new LogicException('Invalid type found.');
        }
    }

    public function with_value($value): self
    {
        return new self($value, $this->type);
    }

    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function get_value()
    {
        return $this->value;
    }

}