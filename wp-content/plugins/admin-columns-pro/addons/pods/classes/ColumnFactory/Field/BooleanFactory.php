<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC;
use AC\FormatterCollection;
use AC\Helper\Select\Option;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACP;
use ACP\Editing\View;

class BooleanFactory extends FieldFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            new View\Toggle(new ToggleOptions(new Option('0'), new Option('1')))
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Checkmark($this->field->get_name());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->field->get_meta_type(), $this->field->get_name());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\YesNoIcon());
    }

}