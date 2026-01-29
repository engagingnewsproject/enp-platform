<?php

declare(strict_types=1);

namespace ACP\Admin;

use AC\ListScreen;
use AC\Type\Url\Preview;
use WP_User;

class Encoder
{

    private ListScreen $list_screen;

    public function __construct(ListScreen $list_screen)
    {
        $this->list_screen = $list_screen;
    }

    public function encode(): array
    {
        return [
            'id'                     => (string)$this->list_screen->get_id(),
            'title'                  => trim($this->list_screen->get_title()) ?: $this->list_screen->get_label(),
            'read_only'              => $this->list_screen->is_read_only(),
            'status'                 => (string)$this->list_screen->get_status(),
            'edit_url'               => (string)$this->list_screen->get_editor_url(),
            'preview_url'            => (string)new Preview($this->list_screen->get_table_url()),
            'restricted_description' => $this->get_restricted_description(),
        ];
    }

    private function get_restricted_description(): string
    {
        $description = [];

        $roles = $this->list_screen->get_preference('roles');
        $users = $this->list_screen->get_preference('users');

        if ($roles) {
            if (1 === count($roles)) {
                $role = $roles[0];
                $description[] = get_editable_roles()[$role]['name'] ?? $role;
            } else {
                $description[] = __('Roles', 'codepress-admin-columns');
            }
        }
        if ($users) {
            if (1 === count($users)) {
                $user = get_userdata($users[0]);

                if ($user instanceof WP_User) {
                    $description[] = ac_helper()->user->get_formatted_name($user)
                        ?: __('User', 'codepress-admin-columns');
                }
            } else {
                $description[] = sprintf('%d %s', count($users), __('Users', 'codepress-admin-columns'));
            }
        }

        return implode(' & ', array_filter($description));
    }

}