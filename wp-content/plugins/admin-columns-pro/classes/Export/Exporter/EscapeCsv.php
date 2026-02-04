<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

use ACP\Export\EscapeData;

class EscapeCsv implements EscapeData
{

    /**
     * @see https://owasp.org/www-community/attacks/CSV_Injection
     */
    public function escape(string $data): string
    {
        if (is_numeric($data)) {
            return $data;
        }

        $characters = [
            '=',
            '+',
            '-',
            '@',
            chr(0x09), // Tab (\t)
            chr(0x0d), // Carriage Return (\r)
        ];

        if (in_array(mb_substr($data, 0, 1), $characters, true)) {
            $data = sprintf("'%s", $data);
        }

        return $data;
    }

}