<?php

namespace ACP\Type;

use ACP\Type\Activation\ExpiryDate;
use ACP\Type\Activation\RenewalMethod;
use ACP\Type\Activation\Status;

final class Activation
{

    private Status $status;

    private RenewalMethod $renewal_method;

    private ?ExpiryDate $expiry_date; // null is lifetime

    public function __construct(
        Status $status,
        RenewalMethod $renewal_method,
        ?ExpiryDate $expiry_date
    ) {
        $this->status = $status;
        $this->renewal_method = $renewal_method;
        $this->expiry_date = $expiry_date;
    }

    public function has_expiry_date(): bool
    {
        return null !== $this->expiry_date;
    }

    public function get_expiry_date(): ExpiryDate
    {
        return $this->expiry_date;
    }

    public function is_lifetime(): bool
    {
        return ! $this->has_expiry_date();
    }

    public function is_expired(): bool
    {
        return $this->has_expiry_date() && $this->expiry_date->is_expired();
    }

    public function get_renewal_method(): RenewalMethod
    {
        return $this->renewal_method;
    }

    public function is_auto_renewal(): bool
    {
        return $this->renewal_method->is_auto_renewal();
    }

    public function get_status(): Status
    {
        return $this->status;
    }

    public function is_active(): bool
    {
        return $this->status->is_active();
    }

    public function is_cancelled(): bool
    {
        return $this->status->is_cancelled();
    }

}