<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACA\Types\Value\Formatter\ReplaceBaseUrl;
use ACP;

class Audio extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_base_formatters(): FormatterCollection
    {
        return parent::get_base_formatters()->add(new ReplaceBaseUrl());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = $this->field->is_repeatable()
            ? new Editing\Storage\RepeatableFile($this->field->get_meta_key(), $this->get_meta_type())
            : new Editing\Storage\File($this->field->get_meta_key(), $this->get_meta_type());

        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Audio())->set_clear_button(true)->set_multiple($this->field->is_repeatable()),
            $storage
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\SearchableText(
            $this->field->get_meta_key(),
            (new ACA\Types\ContextQueryMetaFactory())->create_by_context($this->table_context, $this->field)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->field->is_repeatable()
            ? null
            : (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}