<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Value\Formatter\PodsFieldDisplay;
use ACP;
use ACP\Editing\View;

abstract class BasePickFactory extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    abstract protected function get_options(): array;

    protected function is_multiple(): bool
    {
        return 'multi' === $this->field->get_field()->get_arg('pick_format_type');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\AdvancedSelect())
                ->set_options($this->get_options())
                ->set_multiple($this->is_multiple())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select($this->field->get_name(), $this->get_options());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $options = $this->get_options();
        natcasesort($options);

        return (new ACP\Sorting\Model\MetaMappingFactory())->create(
            (string)$this->field->get_meta_type(),
            $this->field->get_name(),
            array_keys($options)
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new PodsFieldDisplay($this->field),
        ]);
    }

}