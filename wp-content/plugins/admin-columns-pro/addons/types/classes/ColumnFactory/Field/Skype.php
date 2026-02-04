<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACP;

class Skype extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->add(new ACA\Types\Value\Formatter\SkypeLink());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta(new ACP\Search\Operators([
            ACP\Search\Operators::CONTAINS,
            ACP\Search\Operators::IS_EMPTY,
            ACP\Search\Operators::NOT_IS_EMPTY,
        ]), $this->field->get_meta_key());
    }

}