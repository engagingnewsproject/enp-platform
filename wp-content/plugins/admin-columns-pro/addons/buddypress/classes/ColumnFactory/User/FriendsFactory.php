<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\BP\Value\Formatter\User\TotalFriendCount;
use ACP\Column\AdvancedColumnFactory;
use ACP\Search;
use ACP\Sorting;

class FriendsFactory extends AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_friends';
    }

    public function get_label(): string
    {
        return __('Friends', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new TotalFriendCount(),
        ]);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta('total_friend_count');
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Number('total_friend_count');
    }

}