<?php

namespace ACP\Access;

final class PermissionChecker
{

    private PermissionsStorage $permissions_storage;

    /**
     * @var Rule[]
     */
    private array $rules = [];

    private Permissions $permissions;

    public function __construct(PermissionsStorage $permissions_storage)
    {
        $this->permissions_storage = $permissions_storage;
        $this->permissions = new Permissions();
    }

    public function populate(): self
    {
        $this->permissions = $this->permissions_storage->retrieve();

        return $this;
    }

    public function add_rule(Rule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function apply(): void
    {
        $permissions = $this->permissions;

        foreach ($this->rules as $rule) {
            $permissions = $rule->modify($permissions);
        }

        if (Platform::is_local()) {
            $permissions = $permissions->with_usage_permission();
        }

        $this->permissions_storage->save($permissions);
    }

}
