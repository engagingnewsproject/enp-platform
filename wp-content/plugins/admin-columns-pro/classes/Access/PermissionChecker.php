<?php

namespace ACP\Access;

final class PermissionChecker
{

    private $permissions_storage;

    /**
     * @var Rule[]
     */
    private $rules;

    public function __construct(PermissionsStorage $permissions_storage)
    {
        $this->permissions_storage = $permissions_storage;
    }

    public function add_rule(Rule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function apply(): void
    {
        $permissions = new Permissions();

        foreach ($this->rules as $rule) {
            foreach ($rule->get_permissions()->to_array() as $permission) {
                $permissions = $permissions->with_permission($permission);
            }
        }

        $this->permissions_storage->save($permissions);
    }

}