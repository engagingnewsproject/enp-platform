<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface RoleFilterable
{

    public function get_roles(): array;

    public function has_roles(): bool;

}