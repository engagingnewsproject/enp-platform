<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface ValueWrapper
{

    public function get_append(): string;

    public function get_prepend(): string;

}