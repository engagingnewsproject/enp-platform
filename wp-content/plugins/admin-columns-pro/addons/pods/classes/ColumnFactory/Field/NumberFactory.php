<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\Config;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACP;
use ACP\Editing\View;
use ACP\Sorting\Type\DataType;

class NumberFactory extends FieldFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Number())->set_step('any')->set_clear_button(true)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Number($this->field->get_name());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create(
            $this->field->get_meta_type(),
            $this->field->get_name(),
            new DataType(DataType::NUMERIC)
        );
    }

}