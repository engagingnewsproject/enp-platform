<?php

declare(strict_types=1);

namespace ACP\Editing\Setting\ComponentFactory\EditableType;

use AC\Setting\Control\OptionCollection;
use ACP\Editing\Setting\ComponentFactory\EditableType;

class Content extends EditableType
{

    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_WYSIWYG = 'wysiwyg';

    protected function get_default_option(): string
    {
        return self::TYPE_TEXTAREA;
    }

    protected function get_input_options(): OptionCollection
    {
        return OptionCollection::from_array([
            self::TYPE_TEXTAREA => __('Textarea', 'codepress-admin-columns'),
            self::TYPE_WYSIWYG  => __('WYSIWYG', 'codepress-admin-columns'),
        ]);
    }

}