<?php

namespace ACP\Entity;

use ACP\Type\Activation\ExpiryDate;
use ACP\Type\Activation\Products;
use ACP\Type\Activation\RenewalMethod;
use ACP\Type\Activation\Status;

final class Activation
{

    private $status;

    private $renewal_method;

    private $expiry_date;

    private $products;

    public function __construct(
        Status $status,
        RenewalMethod $renewal_method,
        ExpiryDate $expiry_date,
        Products $products
    ) {
        $this->status = $status;
        $this->renewal_method = $renewal_method;
        $this->expiry_date = $expiry_date;
        $this->products = $products;
    }

    public function get_expiry_date(): ExpiryDate
    {
        return $this->expiry_date;
    }

    public function is_lifetime(): bool
    {
        return $this->expiry_date->is_lifetime();
    }

    public function is_expired(): bool
    {
        return $this->expiry_date->is_expired();
    }

    public function get_renewal_method(): RenewalMethod
    {
        return $this->renewal_method;
    }

    public function is_auto_renewal(): bool
    {
        return $this->renewal_method->is_auto_renewal();
    }

    public function is_manual_renewal(): bool
    {
        return $this->renewal_method->is_manual_renewal();
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

    public function get_products(): Products
    {
        return $this->products;
    }

}