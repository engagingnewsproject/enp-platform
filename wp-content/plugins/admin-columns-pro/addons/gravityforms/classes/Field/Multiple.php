<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field;

interface Multiple extends Options
{

    public function is_multiple(): bool;

}