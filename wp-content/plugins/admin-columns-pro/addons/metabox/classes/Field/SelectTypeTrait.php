<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

trait SelectTypeTrait
{

    /*
     * select, select_advanced, select_tree, checkbox_list, checkbox_tree, radio_list
     */
    public function get_select_type(): string
    {
        return isset($this->settings['field_type'])
            ? (string)$this->settings['field_type']
            : 'select';
    }

}