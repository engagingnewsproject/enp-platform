<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface DefaultValue
{

    public function get_default_value(): string;

}