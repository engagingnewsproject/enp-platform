<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Setting\Config;
use ACA;
use ACA\Types\Editing;
use ACP;

class Video extends File
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = $this->field->is_repeatable()
            ? new Editing\Storage\RepeatableFile($this->field->get_meta_key(), $this->get_meta_type())
            : new Editing\Storage\File($this->field->get_meta_key(), $this->get_meta_type());

        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Video())->set_clear_button(true)->set_multiple($this->field->is_repeatable()),
            $storage
        );
    }

}