<?php

namespace ACP\Type\Activation;

use LogicException;

final class Status
{

    private const STATUS_ACTIVE = 'active';
    private const STATUS_CANCELLED = 'cancelled';
    private const STATUS_EXPIRED = 'expired';

    private string $status;

    public function __construct(string $status)
    {
        if ( ! self::is_valid($status)) {
            throw new LogicException('Invalid status.');
        }

        $this->status = $status;
    }

    public function get_value(): string
    {
        return $this->status;
    }

    public function is_active(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function is_cancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public static function is_valid($status): bool
    {
        return in_array($status, [self::STATUS_ACTIVE, self::STATUS_CANCELLED, self::STATUS_EXPIRED], true);
    }

    public function __toString(): string
    {
        return $this->status;
    }

}