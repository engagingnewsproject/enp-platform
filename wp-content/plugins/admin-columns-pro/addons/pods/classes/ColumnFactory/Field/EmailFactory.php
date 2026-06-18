<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\Config;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Sorting\DefaultSortingTrait;
use ACP;
use ACP\Editing\View;

class EmailFactory extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use DefaultSortingTrait;
    use MetaQueryTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Email())
                ->set_clear_button('1' === $this->field->get_arg('email_allow_empty'))
                ->set_placeholder($this->field->get_label())
                ->set_max_length((int)$this->field->get_arg('email_max_length'))
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\SearchableText(
            $this->field->get_name(),
            $this->get_query_meta($this->get_post_type()),
        );
    }

}