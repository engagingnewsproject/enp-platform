<?php

namespace ACP\Search\Comparison\Post\Parent\Meta;

use AC\Helper\Select\Options\Paginated;
use ACP\Helper\Select\User\LabelFormatter\UserName;
use ACP\Helper\Select\User\PaginatedFactory;
use ACP\Search\Comparison;
use ACP\Search\Comparison\Post\Parent\Meta;
use ACP\Search\Operators;

class UserId extends Meta implements Comparison\SearchableValues
{

    public function __construct(string $meta_key)
    {
        parent::__construct(
            $meta_key,
            new Operators([
                Operators::EQ,
            ], false)
        );
    }

    public function format_label($value): string
    {
        $user = get_user_by('id', $value);

        return $user
            ? $this->get_label_formatter()->format_label($user)
            : '';
    }

    protected function get_label_formatter(): UserName
    {
        return new UserName();
    }

    public function get_values(string $search, int $page): Paginated
    {
        return (new PaginatedFactory())->create([
            'search' => $search,
            'paged'  => $page,
        ], $this->get_label_formatter());
    }
}