<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\BP;
use ACA\BP\Value\Formatter\User\UserStatus;
use ACP;
use ACP\Editing;
use ACP\Search;

class StatusFactory extends ACP\Column\AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_status';
    }

    public function get_label(): string
    {
        return __('Status', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new UserStatus(),
        ]);
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new BP\Search\User\Status();
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\User\Status();
    }

}