<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface MaxLength
{

    public function get_maxlength(): int;

    public function has_maxlength(): bool;

}