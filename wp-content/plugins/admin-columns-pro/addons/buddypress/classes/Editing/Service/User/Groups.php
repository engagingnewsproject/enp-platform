<?php

namespace ACA\BP\Editing\Service\User;

use AC\Helper\Select\Options\Paginated;
use ACA\BP\Helper\Select;
use ACP\Editing\PaginatedOptions;
use ACP\Editing\Service;
use ACP\Editing\View;
use BP_Groups_Group;

class Groups implements Service, PaginatedOptions
{

    public function get_view(string $context): ?View
    {
        $view = new View\AjaxSelect();
        $view->set_multiple(true);
        $view->has_methods(true);

        return $view;
    }

    public function format_label($value): string
    {
        $group = bp_get_group_by('id', $value);

        return $group instanceof BP_Groups_Group ? $group->name : $value;
    }

    public function get_value($id)
    {
        $group_ids = groups_get_user_groups($id);
        $groups = [];

        foreach ($group_ids['groups'] as $group_id) {
            $groups[$group_id] = $this->format_label($group_id);
        }

        return $groups;
    }

    public function update(int $user_id, $data): void
    {
        $method = $data['method'] ?: 'replace';
        $value = $data['value'];

        switch ($method) {
            case 'add':
                $this->add_groups($user_id, $value);
                break;
            case 'remove':
                $this->remove_groups($user_id, $value);
                break;
            default:
                $this->replace_groups($user_id, $value);
        }
    }

    private function replace_groups(int $user_id, $new_ids)
    {
        $current_groups = groups_get_user_groups($user_id)['groups'] ?? [];

        $this->remove_groups($user_id, array_diff($current_groups, $new_ids));
        $this->add_groups($user_id, array_diff($new_ids, $current_groups));
    }

    private function add_groups(int $user_id, $group_ids)
    {
        foreach ($group_ids as $group_id) {
            groups_join_group((int)$group_id, $user_id);
        }
    }

    private function remove_groups(int $user_id, $group_ids)
    {
        foreach ($group_ids as $group_id) {
            groups_leave_group((int)$group_id, $user_id);
        }
    }

    public function get_paginated_options(string $search, int $page, int $id = null): Paginated
    {
        $groups = new Select\Groups\Query([
            'search_terms' => $search,
            'page'         => $page,
        ]);

        $options = new Select\Groups\Options($groups->get_copy());

        return new Paginated($groups, $options);
    }

}