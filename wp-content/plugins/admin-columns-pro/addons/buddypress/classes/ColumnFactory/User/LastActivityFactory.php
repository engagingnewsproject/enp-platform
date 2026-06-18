<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\BP\Value\Formatter\User\LastActivity;
use ACP\Column\AdvancedColumnFactory;

class LastActivityFactory extends AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_last_activity';
    }

    public function get_label(): string
    {
        return __('Last Activity', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new LastActivity(),
        ]);
    }

}