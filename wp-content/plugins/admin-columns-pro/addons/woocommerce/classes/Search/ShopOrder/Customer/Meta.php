<?php

declare(strict_types=1);

namespace ACA\WC\Search\ShopOrder\Customer;

use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class Meta extends Comparison\Meta
{

    protected string $related_meta_key;

    public function __construct(string $related_meta_key)
    {
        $operators = new Operators([
            Operators::EQ,
        ]);

        $this->related_meta_key = $related_meta_key;

        parent::__construct($operators, '_customer_user');
    }

    public function get_meta_query(string $operator, Value $value): array
    {
        return [
            'key'     => $this->get_meta_key(),
            'value'   => $this->get_user_ids((string)$value->get_value()),
            'compare' => 'IN',
        ];
    }

    protected function get_user_ids(string $value): array
    {
        return get_users([
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => $this->related_meta_key,
                    'value' => $value,
                ],
            ],
        ]);
    }

}