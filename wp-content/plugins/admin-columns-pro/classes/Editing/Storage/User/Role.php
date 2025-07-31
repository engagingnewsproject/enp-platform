<?php

namespace ACP\Editing\Storage\User;

use ACP\Editing\Storage;
use ACP\RolesFactory;
use WP_User;

class Role implements Storage
{

    private bool $allow_non_editable_roles;

    public function __construct(bool $allow_non_editable_roles)
    {
        $this->allow_non_editable_roles = $allow_non_editable_roles;
    }

    public function get(int $id)
    {
        $roles = get_user_by('id', $id)->roles ?? null;

        return $roles && is_array($roles)
            ? array_values(array_filter($roles, [$this, 'is_editable_role']))
            : false;
    }

    private function get_editable_roles(): array
    {
        static $editable_roles;

        if (null === $editable_roles) {
            $editable_roles = (new RolesFactory())->create($this->allow_non_editable_roles);
        }

        return $editable_roles;
    }

    private function is_editable_role(string $role): bool
    {
        return in_array($role, $this->get_editable_roles(), true);
    }

    private function is_not_editable_role(string $role): bool
    {
        return ! $this->is_editable_role($role);
    }

    public function update(int $id, $data): bool
    {
        if ( ! current_user_can('edit_users') || ! current_user_can('promote_user', $id)) {
            return false;
        }

        $user = get_user_by('id', $id);

        if ( ! $user) {
            return false;
        }

        $params = $data;

        if ( ! isset($params['method'])) {
            $params = [
                'method' => 'replace',
                'value'  => $params,
            ];
        }

        $roles = $params['value'] ?: [];

        switch ($params['method']) {
            case 'add':
                $this->add_roles($user, $roles);

                break;
            case 'remove':
                $this->safely_remove_roles($user, $roles);

                break;
            default:
                $this->replace_roles($user, $roles);
        }

        return true;
    }

    private function replace_roles(WP_User $user, array $roles): void
    {
        foreach ($roles as $role) {
            if ( ! in_array($role, $user->roles, true)) {
                $user->add_role($role);
            }
        }

        $remove = [];

        foreach ($user->roles as $role) {
            if ( ! in_array($role, $roles, true)) {
                $remove[] = $role;
            }
        }

        $this->safely_remove_roles($user, $remove);
    }

    private function add_roles(WP_User $user, array $roles): void
    {
        array_map([$user, 'add_role'], $roles);
    }

    private function safely_remove_roles(WP_User $user, array $roles): void
    {
        $exluded_roles = $this->get_non_removeable_roles($user);

        foreach ($roles as $role) {
            if ( ! in_array($role, $exluded_roles, true)) {
                $user->remove_role($role);
            }
        }
    }

    private function get_non_removeable_roles(WP_User $user): array
    {
        $roles = [];

        // prevent the removal of your own administrator role
        if (get_current_user_id() === $user->ID && current_user_can('administrator')) {
            $roles[] = 'administrator';
        }

        // prevent the removal of existing non-editable roles
        $non_editable_roles = array_values(array_filter($user->roles, [$this, 'is_not_editable_role']));

        return array_merge($roles, $non_editable_roles);
    }

}