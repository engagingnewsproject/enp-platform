<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC;
use AC\Formatter\Implode;
use AC\Formatter\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Search;
use ACA\Types\Value;
use ACA\Types\Value\Formatter\CheckboxLabels;
use ACP;

class Checkboxes extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_base_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new Meta($this->get_meta_type(), $this->field->get_meta_key()),
            new CheckboxLabels($this->get_options()),
            new AC\Formatter\SmallBlocks(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\CheckboxList($this->get_options()))->set_clear_button(true),
            new Editing\Storage\Checkboxes(
                $this->field->get_meta_key(),
                $this->get_meta_type(),
                (array)$this->field->get_data('options')
            )
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Meta($this->get_meta_type(), $this->field->get_meta_key()),
            new CheckboxLabels($this->get_options()),
            new Implode(', '),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Checkboxes($this->field->get_meta_key(), $this->get_options());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

    private function get_options(): array
    {
        $result = [];

        foreach ((array)$this->field->get_data('options') as $option) {
            $result[$option['set_value']] = $option['title'];
        }

        return $result;
    }

}