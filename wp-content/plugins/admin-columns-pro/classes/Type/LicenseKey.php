<?php

namespace ACP\Type;

use LogicException;

final class LicenseKey implements ActivationToken
{

    private string $key;

    public function __construct(string $key)
    {
        if ( ! self::is_valid($key)) {
            throw new LogicException('Invalid license key.');
        }

        $this->key = $key;
    }

    public function get_token(): string
    {
        return $this->key;
    }

    public function get_type(): string
    {
        return 'subscription_key';
    }

    public function equals(LicenseKey $key): bool
    {
        return $this->get_token() === $key->get_token();
    }

    public static function is_valid(string $key): bool
    {
        return $key && strlen($key) > 12 && false !== strpos($key, '-');
    }

}