<?php

declare(strict_types=1);

namespace ACA\WC\Search\Order\Address;

use ACA\WC\Search;
use ACA\WC\Type\AddressType;
use ACP;
use ACP\Query\Bindings;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class FullName extends ACP\Search\Comparison
{

    use Search\Order\OperatorMappingTrait;

    private $address_type;

    public function __construct(AddressType $address_type)
    {
        parent::__construct(
            new Operators([
                Operators::CONTAINS,
            ])
        );

        $this->address_type = $address_type;
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings\QueryArguments();

        $alias = $bindings->get_unique_alias('full_name');

        $bindings->join(
            sprintf(
                "
                JOIN {$wpdb->prefix}wc_order_addresses AS $alias 
                ON {$wpdb->prefix}wc_orders.id = $alias.order_id AND $alias.address_type = '%s'
                ",
                esc_sql((string)$this->address_type)
            )
        );

        $where = ComparisonFactory::create(
            "CONCAT($alias.first_name,' ',$alias.last_name)",
            $operator,
            $value
        );

        return $bindings->where(
            $where()
        );
    }

}