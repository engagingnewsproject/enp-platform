<?php

namespace ACP\Access;

final class Permissions
{

    public const UPDATE = 'update';
    public const USAGE = 'usage';

    private array $permissions = [];

    public function __construct(array $permissions = [])
    {
        foreach ($permissions as $permission) {
            $this->add($permission);
        }
    }

    public function with_usage_permission(): self
    {
        return new self([...$this->permissions, self::USAGE]);
    }

    private function add(string $permission): void
    {
        if (in_array($permission, [self::USAGE, self::UPDATE], true) && ! $this->has_permission($permission)) {
            $this->permissions[] = $permission;
        }
    }

    public function to_array(): array
    {
        return $this->permissions;
    }

    private function has_permission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function has_usage_permission(): bool
    {
        return $this->has_permission(self::USAGE);
    }

    public function has_update_permission(): bool
    {
        return $this->has_permission(self::UPDATE);
    }

}