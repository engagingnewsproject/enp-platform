<?php

declare(strict_types=1);

namespace ACA\Pods\Service;

use AC\Registerable;

class MetaFix implements Registerable
{

    public function register(): void
    {
        if ( ! function_exists('pods_meta')) {
            return;
        }

        add_action('init', static function (): void {
            remove_filter('ac/column/value', [pods_meta(), 'cpac_meta_value']);
        }, 20);
    }

}