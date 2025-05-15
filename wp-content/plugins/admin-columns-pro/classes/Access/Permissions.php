<?php

namespace ACP\Access;

final class Permissions
{

    public const UPDATE = 'update';
    public const USAGE = 'usage';

    private $permissions = [];

    public function __construct(array $permissions = [])
    {
        array_map([$this, 'add'], $permissions);
    }

    private function add(string $permission): void
    {
        if ( ! in_array($permission, [self::USAGE, self::UPDATE], true)) {
            return;
        }

        if (in_array($permission, $this->permissions, true)) {
            return;
        }

        $this->permissions[] = $permission;
    }

    public function with_permission(string $permission): self
    {
        $permissions = new self($this->permissions);
        $permissions->add($permission);

        return $permissions;
    }

    public function has_permission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function has_usage_permission(): bool
    {
        return $this->has_permission(self::USAGE);
    }

    public function has_updates_permission(): bool
    {
        return $this->has_permission(self::UPDATE);
    }

    public function to_array(): array
    {
        return $this->permissions;
    }

}