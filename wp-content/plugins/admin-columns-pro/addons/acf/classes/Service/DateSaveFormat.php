<?php

declare(strict_types=1);

namespace ACA\ACF\Service;

use AC\Registerable;

class DateSaveFormat implements Registerable
{

    public function register(): void
    {
        add_filter('ac/column/date_save_format/options', [$this, 'date_save_format_options']);
    }

    public function date_save_format_options(array $options): array
    {
        $options['Ymd'] = sprintf(
            'ACF %s (%s)',
            __('Date Format', 'codepress-admin-columns'),
            'Ymd'
        );

        return $options;
    }

}