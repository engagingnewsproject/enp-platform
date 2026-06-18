<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface MaxLength
{

    public function get_max_length(): ?int;

}