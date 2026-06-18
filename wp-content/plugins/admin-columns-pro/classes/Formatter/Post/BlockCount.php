<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class BlockCount implements AC\Formatter
{

    public function format(Value $value)
    {
        $values = [];

        foreach ($this->count_blocks((array)$value->get_value()) as $name => $block_count) {
            $values[] = sprintf('%s <span class="ac-rounded">%s</span>', $name, $block_count);
        }

        return $value->with_value(implode('<br>', $values));
    }

    private function count_blocks(array $blocks, array $grouped_count = [])
    {
        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                continue;
            }

            $name = $block['blockName'];

            if ( ! isset($grouped_count[$name])) {
                $grouped_count[$name] = 1;
            } else {
                $grouped_count[$name]++;
            }

            if ( ! empty($block['innerBlocks'])) {
                $grouped_count = $this->count_blocks(
                    $block['innerBlocks'],
                    $grouped_count
                );
            }
        }

        return $grouped_count;
    }

}