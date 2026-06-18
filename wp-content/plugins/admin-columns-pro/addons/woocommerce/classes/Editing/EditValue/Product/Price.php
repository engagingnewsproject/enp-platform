<?php

declare(strict_types=1);

namespace ACA\WC\Editing\EditValue\Product;

class Price
{

    private string $type;

    private string $price_type;

    private float $price;

    private float $percentage;

    private bool $rounding;

    private string $rounding_type = '';

    private int $rounding_decimals = 0;

    public function __construct(array $value)
    {
        $this->type = (string)($value['type'] ?? '');
        $this->price_type = (string)($value['price']['type'] ?? '');
        $this->price = (float)($value['price']['value'] ?? 0);

        $this->percentage = (float)($value['price']['value'] ?? 0);
        $this->rounding = $value['rounding']['active'] === 'true';

        if ($this->rounding) {
            $this->rounding_type = (string)($value['rounding']['type'] ?? '');
            $this->rounding_decimals = absint($value['rounding']['decimals']);
        }
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function get_price_type(): string
    {
        return $this->price_type;
    }

    public function get_price(): float
    {
        return $this->price;
    }

    public function get_percentage(): float
    {
        return $this->percentage;
    }

    public function is_rounded(): bool
    {
        return $this->rounding;
    }

    public function get_rounding_type(): string
    {
        return $this->rounding_type;
    }

    public function get_rounding_decimals(): int
    {
        return $this->rounding_decimals;
    }

}