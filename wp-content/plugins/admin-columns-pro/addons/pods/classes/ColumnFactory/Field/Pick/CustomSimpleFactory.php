<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use PodsField_Pick;
use PodsForm;

class CustomSimpleFactory extends BasePickFactory
{

    protected function get_options(): array
    {
        $_field = PodsForm::field_loader('pick');

        if ( ! $_field instanceof PodsField_Pick) {
            return [];
        }

        return (array)$_field->get_field_data($this->field->get_field());
    }

}