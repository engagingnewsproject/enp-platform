<?php

namespace ACP\Sorting\UserPreference;

use AC;
use AC\Preferences\SiteFactory;
use ACP\Sorting\Type;

class SortType
{

    private const OPTION_ORDER = 'order';
    private const OPTION_ORDERBY = 'orderby';

    private string $key;

    private SiteFactory $storage_factory;

    public function __construct(string $key, SiteFactory $storage_factory)
    {
        $this->key = $key;
        $this->storage_factory = $storage_factory;
    }

    public static function create(AC\ListScreen $list_screen): self
    {
        return new self($list_screen->get_table_id() . $list_screen->get_id(), new SiteFactory());
    }

    private function storage(): AC\Preferences\Preference
    {
        return $this->storage_factory->create('sorted_by');
    }

    public function get(): ?Type\SortType
    {
        $data = $this->storage()->find($this->key);

        $order_by = $data[self::OPTION_ORDERBY] ?? null;

        if ( ! Type\SortType::validate($order_by)) {
            return null;
        }

        return new Type\SortType(
            $order_by,
            'asc' !== $data[self::OPTION_ORDER]
        );
    }

    public function delete(): void
    {
        $this->storage()->delete($this->key);
    }

    public function save(Type\SortType $sort_type): void
    {
        $this->storage()->save($this->key, [
            self::OPTION_ORDERBY => $sort_type->get_order_by(),
            self::OPTION_ORDER   => $sort_type->is_descending() ? 'desc' : 'asc',
        ]);
    }

}