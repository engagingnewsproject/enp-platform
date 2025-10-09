<?php

namespace AltchaOrg\Altcha;

class ChallengeOptions
{
    public $algorithm;
    public $maxNumber;
    public $saltLength;
    public $hmacKey;
    public $salt;
    public $number;
    public $expires;
    public $params;

    public function __construct($options = [])
    {
        $this->algorithm = $options['algorithm'] ?? Altcha::DEFAULT_ALGORITHM;
        $this->maxNumber = $options['maxNumber'] ?? Altcha::DEFAULT_MAX_NUMBER;
        $this->saltLength = $options['saltLength'] ?? Altcha::DEFAULT_SALT_LENGTH;
        $this->hmacKey = $options['hmacKey'] ?? '';
        $this->salt = $options['salt'] ?? '';
        $this->number = $options['number'] ?? 0;
        $this->expires = $options['expires'] ?? null;
        $this->params = $options['params'] ?? [];
    }
}