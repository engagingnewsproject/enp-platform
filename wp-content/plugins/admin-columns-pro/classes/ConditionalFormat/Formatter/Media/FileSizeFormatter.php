<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\Media;

use AC\Column;
use ACP\ConditionalFormat\Formatter;
use ACP\Expression\ComparisonOperators;

class FileSizeFormatter implements Formatter
{

    public function get_type(): string
    {
        return self::INTEGER;
    }

    public function format(string $value, $id, Column $column, string $operator_group): string
    {
        if (ComparisonOperators::class === $operator_group) {
            $value = '';
            $abs = get_attached_file($id);

            if (file_exists($abs)) {
                $value = (string)floor(filesize($abs) / 1024);
            }

            return $value;
        }

        return $value;
    }
}