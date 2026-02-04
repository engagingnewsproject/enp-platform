<?php

declare(strict_types=1);

namespace ACP\Query;

trait SqlTrait
{

    protected function esc_sql_array(array $array): string
    {
        return sprintf("'%s'", implode("','", array_map('esc_sql', $array)));
    }

}