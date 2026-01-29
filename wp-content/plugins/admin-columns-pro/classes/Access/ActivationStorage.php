<?php

namespace ACP\Access;

use AC\Storage\OptionData;
use AC\Storage\OptionDataFactory;
use ACP\Type\Activation;
use ACP\Type\Activation\ExpiryDate;
use ACP\Type\Activation\Key;
use ACP\Type\Activation\RenewalMethod;
use ACP\Type\Activation\Status;
use ACP\Type\ActivationToken;
use DateTime;
use Exception;

final class ActivationStorage
{

    public const PARAM_STATUS = 'status';
    public const PARAM_RENEWAL_METHOD = 'renewal_method';
    public const PARAM_EXPIRY_DATE = 'expiry_date';

    private OptionData $activation_storage;

    private OptionData $token_storage;

    private ?string $token = null;

    private ?array $activation = null;

    public function __construct(OptionDataFactory $option_factory)
    {
        $this->activation_storage = $option_factory->create('acp_subscription_details');
        $this->token_storage = $option_factory->create('acp_subscription_details_key');
    }

    public function find(ActivationToken $activation_token): ?Activation
    {
        if ($this->get_token() !== $activation_token->get_token()) {
            return null;
        }

        $data = $this->get_activation();

        if (empty($data)) {
            return null;
        }

        // Check required params
        $params = [
            self::PARAM_STATUS,
            self::PARAM_RENEWAL_METHOD,
            self::PARAM_EXPIRY_DATE,
        ];

        foreach ($params as $param) {
            if ( ! array_key_exists($param, $data)) {
                return null;
            }
        }

        if ( ! Status::is_valid($data[self::PARAM_STATUS])) {
            return null;
        }

        if ( ! RenewalMethod::is_valid($data[self::PARAM_RENEWAL_METHOD])) {
            return null;
        }

        if (null === $data[self::PARAM_EXPIRY_DATE]) {
            $expire_date = null;
        } else {
            try {
                $expire_date = new DateTime();
                $expire_date->setTimestamp($data[self::PARAM_EXPIRY_DATE]);
            } catch (Exception $e) {
                return null;
            }

            // Lifetime
            if ($expire_date > DateTime::createFromFormat('Y-m-d', '2037-12-30')) {
                $expire_date = null;
            }
        }

        return new Activation(
            new Status($data[self::PARAM_STATUS]),
            new RenewalMethod($data[self::PARAM_RENEWAL_METHOD]),
            $expire_date
                ? new ExpiryDate($expire_date)
                : null
        );
    }

    public function save(Key $key, Activation $activation): void
    {
        $data = [
            self::PARAM_STATUS         => $activation->get_status()->get_value(),
            self::PARAM_RENEWAL_METHOD => $activation->get_renewal_method()->get_value(),
            self::PARAM_EXPIRY_DATE    => $activation->has_expiry_date()
                ? $activation->get_expiry_date()->get_value()->getTimestamp()
                : null,
        ];

        $this->activation_storage->save($data);
        $this->token_storage->save($key->get_token());

        $this->flush_cache();
    }

    public function delete(): void
    {
        $this->activation_storage->delete();
        $this->token_storage->delete();

        $this->flush_cache();
    }

    private function flush_cache(): void
    {
        $this->token = null;
        $this->activation = null;
    }

    private function get_token(): string
    {
        if (null === $this->token) {
            $this->token = (string)$this->token_storage->get();
        }

        return $this->token;
    }

    private function get_activation(): array
    {
        if (null === $this->activation) {
            $this->activation = $this->activation_storage->get() ?: [];
        }

        return $this->activation;
    }

}