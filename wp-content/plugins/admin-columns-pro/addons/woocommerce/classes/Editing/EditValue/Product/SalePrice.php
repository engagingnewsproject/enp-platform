<?php

declare(strict_types=1);

namespace ACA\WC\Editing\EditValue\Product;

class SalePrice extends Price
{

    private bool $price_based_on_regular;

    private bool $scheduled;

    private string $schedule_from = '';

    private string $schedule_to = '';

    public function __construct(array $value)
    {
        parent::__construct($value);

        $this->price_based_on_regular = 'true' === $value['price']['based_on_regular'];
        $this->scheduled = 'true' === $value['schedule']['active'];

        if ($this->scheduled) {
            $this->schedule_from = $value['schedule']['from'] ?? '';
            $this->schedule_to = $value['schedule']['to'] ?? '';

            if ($this->schedule_to) {
                $this->schedule_to = (string)date('Y-m-d 23:59:59', strtotime($this->schedule_to));
            }
        }
    }

    public function is_price_based_on_regular(): bool
    {
        return $this->price_based_on_regular;
    }

    public function is_scheduled(): bool
    {
        return $this->scheduled;
    }

    public function get_schedule_from(): string
    {
        return $this->schedule_from;
    }

    public function get_schedule_to(): string
    {
        return $this->schedule_to;
    }

}