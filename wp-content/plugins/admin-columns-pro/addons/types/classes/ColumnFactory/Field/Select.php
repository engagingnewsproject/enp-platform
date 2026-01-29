<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Value;
use ACP;

class Select extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->add(
            new AC\Formatter\MapOptionLabel($this->get_options())
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Select($this->get_options()))->set_clear_button(true),
            new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select($this->field->get_meta_key(), $this->get_options());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $options = $this->get_options();
        natcasesort($options);

        return (new ACP\Sorting\Model\MetaMappingFactory())->create(
            (string)$this->get_meta_type(),
            $this->field->get_meta_key(),
            array_keys($options)
        );
    }

    private function get_options(): array
    {
        $result = [];

        foreach ((array)$this->field->get_data('options') as $option) {
            if ( ! is_array($option)) {
                continue;
            }

            $result[$option['value']] = $option['title'];
        }

        return $result;
    }

}