<?php

declare(strict_types=1);

namespace ACA\RankMath\Editing\Storage\User;

use ACP\Editing\Storage;

final class ProfileUrls implements Storage
{

    public function get(int $id)
    {
        return explode(' ', (string)get_user_meta($id, 'additional_profile_urls', true));
    }

    public function update(int $id, $data): bool
    {
        $joined_data = implode(' ', $data);
        update_user_meta($id, 'additional_profile_urls', $joined_data);

        return true;
    }

}