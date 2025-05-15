<?php

namespace ACA\ACF\Export\Model\Repeater;

use ACA;
use ACA\ACF\Column;
use ACP;

class Boolean implements ACP\Export\Service
{

    private $column;

    private $key;

    public function __construct(Column\Repeater $column, string $key)
    {
        $this->column = $column;
        $this->key = $key;
    }

    public function get_value($id)
    {
        $value = $this->column->get_raw_value($id);
        $values = wp_list_pluck((array)$value, $this->key);

        $delimiter = (string)apply_filters('acp/acf/export/repeater/delimiter', ';', $this->column);

        return implode($delimiter, $values);
    }

}