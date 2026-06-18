<?php

declare(strict_types=1);

namespace ACA\WC\Type;

use LogicException;

final class AddressType
{

    public const BILLING = 'billing';
    public const SHIPPING = 'shipping';

    private string $address_type;

    public function __construct(string $address_type)
    {
        $this->address_type = $address_type;

        $this->validate();
    }

    public static function billing(): AddressType
    {
        return new AddressType(self::BILLING);
    }

    public static function shipping(): AddressType
    {
        return new AddressType(self::SHIPPING);
    }

    public function get(): string
    {
        return $this->address_type;
    }

    /**
     * @throws LogicException
     */
    private function validate(): void
    {
        $types = [
            self::BILLING,
            self::SHIPPING,
        ];

        if ( ! in_array($this->address_type, $types, true)) {
            throw new LogicException('Invalid address type.');
        }
    }

    public function __toString(): string
    {
        return $this->address_type;
    }

}