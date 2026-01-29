<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACP;
use ACP\Editing\View;

class CodeFactory extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\TextArea())->set_clear_button(true)->set_placeholder($this->field->get_label())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->field->get_name());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->field->get_meta_type(), $this->field->get_name());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new AC\Formatter\CodeBlock()
        );
    }

}