<?php

namespace ACP\Search\Middleware;

abstract class Mapping
{

    public const RESPONSE = 'response';
    public const REQUEST = 'request';

    protected string $direction;

    protected array $properties;

    public function __construct(?string $direction = null)
    {
        if ($direction !== self::REQUEST) {
            $direction = self::RESPONSE;
        }

        $this->direction = $direction;
        $this->properties = $this->apply_direction(
            $this->get_properties()
        );
    }

    protected function apply_direction(array $array): array
    {
        if ($this->direction === self::REQUEST) {
            $array = array_flip($array);
        }

        return $array;
    }

    /**
     * Return array of properties with the response side first
     */
    abstract protected function get_properties(): array;

    public function get(string $key): ?string
    {
        return $this->properties[$key] ?? null;
    }

    public function __get(string $key): ?string
    {
        return $this->get($key);
    }

}