<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Meta;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Value;

final class GroupCloneFactory extends GroupFactory
{

    protected function get_raw_formatter(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\GroupCloneField(
                $this->table_context->get_meta_type(),
                $this->field->get_id(),
                $config->get('group_field', '')
            ),
        ]);
    }

}