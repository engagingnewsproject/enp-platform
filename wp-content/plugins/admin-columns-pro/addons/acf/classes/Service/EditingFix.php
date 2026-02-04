<?php

declare(strict_types=1);

namespace ACA\ACF\Service;

use AC\Registerable;

class EditingFix implements Registerable
{

    public function register(): void
    {
        add_filter('ac/editing/post_statuses', [$this, 'remove_acf_statuses_for_editing']);
    }

    public function remove_acf_statuses_for_editing($statuses)
    {
        if (isset($statuses['acf-disabled'])) {
            unset($statuses['acf-disabled']);
        }

        return $statuses;
    }

}