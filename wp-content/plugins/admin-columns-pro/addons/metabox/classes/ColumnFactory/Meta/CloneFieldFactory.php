<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Meta;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Value;

class CloneFieldFactory extends FieldFactory
{

    protected function get_base_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\MetaboxCloneValue($this->table_context->get_meta_type(), $this->field->get_id()),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->add(
            new Separator('<div class="ac-mb-divider"></div>')
        );

        return $formatters;
    }

}