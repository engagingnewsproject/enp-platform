<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface DefaultValue
{

    public function get_default_value(): ?string;

    public function has_default_value(): bool;

}