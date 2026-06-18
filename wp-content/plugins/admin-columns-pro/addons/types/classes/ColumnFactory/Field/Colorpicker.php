<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACP;

class Colorpicker extends FieldFactory
{

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->add(new AC\Formatter\Color());
    }

    protected function get_post_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Collection\Separator(''),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = $this->field->is_repeatable()
            ? new Editing\Storage\Repeater($this->field->get_meta_key(), $this->get_meta_type())
            : new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type());

        $view = $this->field->is_repeatable()
            ? (new ACP\Editing\View\MultiInput())->set_clear_button(true)->set_sub_type('color')
            : (new ACP\Editing\View\Color())->set_clear_button(true);

        return new ACP\Editing\Service\Basic($view, $storage);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->field->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}