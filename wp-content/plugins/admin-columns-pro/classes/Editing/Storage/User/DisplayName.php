<?php

namespace ACP\Editing\Storage\User;

use ACP\Editing\Storage;

class DisplayName implements Storage
{

    public function get($id): string
    {
        $user = get_userdata($id);
        return $user ? ($user->display_name ?? '') : '';
    }

    public function update(int $id, $data): bool
    {
        global $wpdb;

        $data = sanitize_user($data, true);

        $result = $wpdb->update(
            $wpdb->users,
            ['display_name' => $data],
            ['ID' => $id],
            ['%s'],
            ['%d']
        );

        clean_user_cache($id);

        return $result !== false;
    }

}