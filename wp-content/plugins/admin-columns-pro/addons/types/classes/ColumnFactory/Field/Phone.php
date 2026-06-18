<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACP;
use ACP\Sorting\Type\DataType;

class Phone extends FieldFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return $this->field->is_repeatable()
            ? new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\MultiInput())->set_clear_button(true),
                new Editing\Storage\Repeater($this->field->get_meta_key(), $this->get_meta_type())
            )
            : new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\Text())->set_clear_button(true),
                new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type())
            );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->field->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create(
                $this->get_meta_type(),
                $this->field->get_meta_key(),
                new DataType(DataType::NUMERIC)
            );
    }

}