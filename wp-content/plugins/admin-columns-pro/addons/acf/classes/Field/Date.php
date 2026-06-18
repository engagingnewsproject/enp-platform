<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface Date
{

    public function get_display_format(): string;

    public function get_first_day(): int;
}