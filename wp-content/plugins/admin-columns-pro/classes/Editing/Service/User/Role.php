<?php

namespace ACP\Editing\Service\User;

use AC;
use ACP\Editing;
use ACP\Editing\Service;
use ACP\Editing\Service\Editability;
use ACP\Editing\View;

class Role implements Service, Editability
{

    /**
     * By default, WordPress does not allow you to edit certain (3rd party) roles
     */
    private bool $allow_non_editable_roles;

    private Editing\Storage\User\Role $storage;

    public function __construct(bool $allow_non_editable_roles)
    {
        $this->allow_non_editable_roles = $allow_non_editable_roles;
        $this->storage = new Editing\Storage\User\Role($this->allow_non_editable_roles);
    }

    public function get_view(string $context): ?View
    {
        $view = new Editing\View\AdvancedSelect($this->get_options());
        $view->set_clear_button(false)
             ->set_multiple(true);

        if ($context === self::CONTEXT_BULK) {
            $view->has_methods(true);
        }

        return $view;
    }

    public function get_not_editable_reason(int $id): string
    {
        return __('Current user can not change user role.', 'codepress-admin-columns');
    }

    public function get_value(int $id)
    {
        return $this->storage->get($id);
    }

    public function update(int $id, $data): void
    {
        $this->storage->update($id, $data);
    }

    public function is_editable(int $id): bool
    {
        return current_user_can('edit_users') && current_user_can('promote_user', $id);
    }

    private function get_roles(): AC\Type\UserRoles
    {
        static $editable_roles;

        if (null === $editable_roles) {
            $editable_roles = (new AC\Helper\UserRoles())->find_all($this->allow_non_editable_roles);
        }

        return $editable_roles;
    }

    private function get_options(): array
    {
        $options = [];

        foreach ($this->get_roles() as $role) {
            $options[$role->get_name()] = $role->get_translate_label();
        }

        asort($options);

        return $options;
    }

}