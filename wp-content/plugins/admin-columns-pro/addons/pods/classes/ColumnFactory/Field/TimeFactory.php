<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\Config;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Sorting\DefaultSortingTrait;
use ACP;
use ACP\Editing\View;

class TimeFactory extends FieldFactory
{

    use DefaultSortingTrait;
    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Text())->set_clear_button(true)->set_placeholder($this->field->get_label())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(
            $this->field->get_name()
        );
    }

}