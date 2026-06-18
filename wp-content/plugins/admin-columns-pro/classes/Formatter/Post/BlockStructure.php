<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;

class BlockStructure implements AC\Formatter
{

    public function format(Value $value): ValueCollection
    {
        $data = (array)$value->get_value();

        if ( ! $data) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $blocks = $this->get_block_structure($data);

        if ( ! $blocks) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        foreach ($blocks as $block) {
            $values[] = new Value($value->get_id(), $block);
        }

        return new ValueCollection(
            $value->get_id(),
            $values
        );
    }

    private function get_block_structure(array $blocks, array $values = [], string $prefix = ''): array
    {
        foreach ($blocks as $block) {
            if (isset($block['blockName']) && $block['blockName']) {
                $values[] = sprintf('%s[%s]', $prefix, $block['blockName']);
            }

            if ( ! empty($block['innerBlocks'])) {
                $values = $this->get_block_structure($block['innerBlocks'], $values, $prefix . '&nbsp;&nbsp;&nbsp;');
            }
        }

        return $values;
    }

}