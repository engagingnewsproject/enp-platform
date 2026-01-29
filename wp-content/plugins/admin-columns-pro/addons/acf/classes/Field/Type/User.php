<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class User extends Field implements Field\Multiple, Field\RoleFilterable
{

    use MultipleTrait;

    public function has_roles(): bool
    {
        return isset($this->settings['role']) && ! empty($this->settings['role']);
    }

    public function get_roles(): array
    {
        return $this->has_roles()
            ? (array)$this->settings['role']
            : [];
    }

}