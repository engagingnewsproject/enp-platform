<?php

declare(strict_types=1);

namespace ACA\JetEngine\Editing\Service\Relation;

use AC\Helper\Select\Options\Paginated;
use ACA\JetEngine\Editing;
use ACP\Helper\Select\User\PaginatedFactory;

class User extends Editing\Service\Relationship
{

    public function get_value(int $id): array
    {
        $value = [];
        $user_ids = parent::get_value($id);

        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);

            if ( ! $user) {
                continue;
            }

            $value[$user_id] = ac_helper()->user->get_formatted_name($user);
        }

        return $value;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            'paged'  => $page,
            'search' => $search,
        ]);
    }

}