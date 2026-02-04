<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Setting\Config;
use ACA;
use ACA\Types\ColumnFactory\FieldFactory;
use ACA\Types\Editing;
use ACP;

class Email extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        if ($this->field->is_repeatable()) {
            return new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\MultiInput())->set_clear_button(true)->set_sub_type('email'),
                new Editing\Storage\Repeater($this->field->get_meta_key(), $this->get_meta_type())
            );
        }

        $validation = $this->field->get_data('validate');
        $view = $validation && isset($validation['url']['active']) && 1 === (int)$validation['url']['active']
            ? new ACP\Editing\View\Url()
            : new ACP\Editing\View\Text();

        return new ACP\Editing\Service\Basic(
            $view->set_clear_button(true),
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
            : (new ACP\Sorting\Model\MetaFactory())->create($this->get_meta_type(), $this->field->get_meta_key());
    }

}