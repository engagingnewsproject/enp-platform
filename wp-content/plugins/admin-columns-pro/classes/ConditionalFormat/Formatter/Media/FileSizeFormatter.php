<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\Media;

use AC\Expression\ComparisonOperators;
use ACP\ConditionalFormat\Formatter\BaseFormatter;

class FileSizeFormatter extends BaseFormatter
{

    public function __construct()
    {
        parent::__construct(self::FLOAT);
    }

    public function format(string $value, $id, string $operator_group): string
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