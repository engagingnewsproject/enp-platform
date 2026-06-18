<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Setting\Config;
use ACA;
use ACA\Types\Editing;
use ACP;

class TextArea extends TextField
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        if ($this->field->is_repeatable()) {
            return new ACP\Editing\Service\Basic(
                (new ACP\Editing\View\MultiInput())->set_clear_button(true)->set_sub_type('textarea'),
                new Editing\Storage\Repeater($this->field->get_meta_key(), $this->get_meta_type())
            );
        }

        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\TextArea())->set_clear_button(true),
            new ACP\Editing\Storage\Meta($this->field->get_meta_key(), $this->get_meta_type())
        );
    }

}