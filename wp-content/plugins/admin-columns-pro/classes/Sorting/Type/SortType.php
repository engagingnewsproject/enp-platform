<?php

namespace ACP\Sorting\Type;

use AC\ListScreen;
use ACP\Sorting\ApplyFilter\DefaultSort;
use InvalidArgumentException;

class SortType
{

    private string $order_by;

    private bool $descending;

    public function __construct(string $order_by, bool $descending = true)
    {
        $this->order_by = $order_by;
        $this->descending = $descending;

        if ( ! self::validate($order_by)) {
            throw new InvalidArgumentException('String can not be empty.');
        }
    }

    public static function validate($order_by): bool
    {
        return is_string($order_by) && '' !== $order_by;
    }

    public function get_order_by(): string
    {
        return $this->order_by;
    }

    public function is_descending(): bool
    {
        return $this->descending;
    }

    public function equals(SortType $sort_type): bool
    {
        return $sort_type->is_descending() === $this->descending &&
               $sort_type->get_order_by() === $this->order_by;
    }

    public static function create_by_list_screen(ListScreen $list_screen): ?self
    {
        $order_by = $list_screen->get_preference('sorting');

        if ( ! self::validate($order_by)) {
            return null;
        }

        $sort_type = new self(
            $order_by,
            'asc' !== $list_screen->get_preference('sorting_order')
        );

        return (new DefaultSort($list_screen))->apply_filters($sort_type);
    }

    public static function create_by_request_globals(): ?self
    {
        $order_by = $_GET['orderby'] ?? null;

        if ( ! self::validate($order_by)) {
            return null;
        }

        $order = $_GET['order'] ?? '';

        return new self(
            $order_by,
            'asc' !== strtolower($order)
        );
    }

}