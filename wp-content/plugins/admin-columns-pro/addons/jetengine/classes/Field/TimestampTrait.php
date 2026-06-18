<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

trait TimestampTrait
{

    public function is_timestamp(): bool
    {
        return isset($this->settings['is_timestamp']) && $this->settings['is_timestamp'];
    }

}