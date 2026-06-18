<?php

declare(strict_types=1);

namespace ACP\Helper\Select\User\GroupFormatter;

use ACP\Helper\Select\User\GroupFormatter;
use WP_User;

class Role implements GroupFormatter
{

    public function format(WP_User $user): string
    {
        $user_role = $user->roles[0] ?? '';

        $role_name = wp_roles()->roles[$user_role]['name'] ?? null;

        if ( ! $role_name) {
            return __('No Role', 'codepress-admin-columns');
        }

        return translate_user_role($role_name);
    }

}