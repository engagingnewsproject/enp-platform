<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Formatter\Prepend;
use AC\Formatter\YesIcon;
use AC\FormatterCollection;
use AC\Helper\Select\Option;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Search;
use ACA\Types\Value;
use ACP;

class Checkbox extends FieldFactory
{

    protected function get_base_formatters(): FormatterCollection
    {
        $formatters = parent::get_base_formatters();

        if ('db' === (string)$this->field->get_data('display')) {
            $formatters->add(
                new Prepend(
                    new YesIcon()
                )
            );
        }

        if ('value' === (string)$this->field->get_data('display')) {
            $formatters->add(
                new Value\Formatter\CheckboxToggleValue(
                    (string)$this->field->get_data('display_value_selected'),
                    (string)$this->field->get_data('display_value_not_selected')
                )
            );
        }

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Toggle(
                new ToggleOptions(
                    new Option($this->field->get_data('save_empty') === 'yes' ? '0' : ''),
                    new Option((string)$this->field->get_data('set_value'))
                )
            ),
            new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Checkbox($this->field->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}