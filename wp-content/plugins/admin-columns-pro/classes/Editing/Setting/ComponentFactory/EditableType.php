<?php

declare(strict_types=1);

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

abstract class EditableType extends BaseComponentFactory
{

    public const NAME = 'editable_type';

    protected function get_label(Config $config): ?string
    {
        return __('Input Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_input_options(),
            $config->get(self::NAME, $this->get_default_option())
        );
    }

    abstract protected function get_input_options(): OptionCollection;

    abstract protected function get_default_option(): string;

}