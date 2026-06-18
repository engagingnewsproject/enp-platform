<?php

declare(strict_types=1);

namespace ACA\Pods\Column;

use AC\Column\CustomFieldContext;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use Pods\Whatsit;

class FieldContext extends CustomFieldContext
{

    private Whatsit\Field $field;

    public function __construct(Config $config, string $label, Whatsit\Field $field, TableScreenContext $table_context)
    {
        parent::__construct($config, $label, $field->get_type(), $field->get_name(), $table_context);

        $this->field = $field;
    }

    public function get_field(): Whatsit\Field
    {
        return $this->field;
    }

}