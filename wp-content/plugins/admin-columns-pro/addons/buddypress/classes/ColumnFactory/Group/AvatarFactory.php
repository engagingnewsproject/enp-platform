<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group;

use AC\Column\BaseColumnFactory;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\BP\Value\Formatter\Group\Avatar;

class AvatarFactory extends BaseColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-group_avatar';
    }

    public function get_label(): string
    {
        return __('Avatar', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Avatar());
    }

}