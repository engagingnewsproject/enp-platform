<?php

declare(strict_types=1);

namespace ACP\Type\Activation;

use DateTime;

class ExpiryDate
{

    private DateTime $expiry_date;

    public function __construct(DateTime $expiry_date)
    {
        $this->expiry_date = $expiry_date;
    }

    private function now(): DateTime
    {
        return new DateTime();
    }

    public function get_value(): DateTime
    {
        return $this->expiry_date;
    }

    public function is_expired(): bool
    {
        return $this->expiry_date < $this->now();
    }

    public function get_expired_seconds(): int
    {
        return $this->now()->getTimestamp() - $this->expiry_date->getTimestamp();
    }

    private function get_remaining_seconds(): int
    {
        return $this->expiry_date->getTimestamp() - $this->now()->getTimestamp();
    }

    public function get_remaining_days(): float
    {
        return (float)($this->get_remaining_seconds() / DAY_IN_SECONDS);
    }

    public function is_expiring_within_seconds(int $seconds): bool
    {
        return $this->get_remaining_seconds() < $seconds;
    }

    public function get_human_time_diff(): string
    {
        return human_time_diff($this->expiry_date->getTimestamp(), $this->now()->getTimestamp());
    }

}