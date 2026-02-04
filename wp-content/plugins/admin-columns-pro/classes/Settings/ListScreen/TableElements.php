<?php

namespace ACP\Settings\ListScreen;

class TableElements
{

    private array $items;

    public function __construct(array $items = [])
    {
        array_map([$this, 'add'], $items);
    }

    public function add(TableElement $table_element, int $priority = 10): self
    {
        $this->items[] = [
            'priority' => $priority,
            'item'     => $table_element,
        ];

        return $this;
    }

    public function remove(TableElement $table_element): self
    {
        foreach ($this->items as $k => $item) {
            if ($item['item']->get_name() === $table_element->get_name()) {
                unset($this->items[$k]);
            }
        }

        return $this;
    }

    /**
     * @return TableElement[]
     */
    public function all(): array
    {
        return array_map([$this, 'pluck_item'], $this->sorted_by_priority());
    }

    private function pluck_item(array $item)
    {
        return $item['item'];
    }

    private function sorted_by_priority(): array
    {
        $sorted = [];

        foreach ($this->items as $item) {
            $sorted[$item['priority']][] = $item;
        }

        ksort($sorted, SORT_NUMERIC);

        return array_merge(...$sorted);
    }

}