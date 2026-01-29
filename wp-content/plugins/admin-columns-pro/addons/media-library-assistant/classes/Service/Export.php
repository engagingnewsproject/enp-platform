<?php

declare(strict_types=1);

namespace ACA\MLA\Service;

use AC;
use AC\Column\Context;
use AC\Registerable;

class Export implements Registerable
{

    public function register(): void
    {
        add_filter('ac/export/row_headers', [$this, 'fix_excel_issue'], 10, 2);
        add_filter('ac/export/render', [$this, 'strip_tags_value'], 10, 4);
    }

    public function strip_tags_value(string $value, Context $context, string $row_id, AC\TableScreen $table_screen)
    {
        if ( ! $table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return $value;
        }

        return strip_tags((string)$value);
    }

    /**
     * Error 'SYLK: File format is not valid' in Excel
     * MS Excel 2003 and 2013 does not allow the first label to start with 'ID'
     */
    public function fix_excel_issue(array $headers, AC\TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return $headers;
        }

        foreach ($headers as $name => $label) {
            $first = substr($label, 0, 2);
            $end = substr($label, 2);

            // Rename label 'ID' to 'id'
            if ('ID' === $first) {
                $headers[$name] = strtolower($first) . $end;
            }
            break;
        }

        return $headers;
    }

}