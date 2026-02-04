<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class BlockStructure implements AC\Formatter
{

    private ?int $limit;

    public function __construct(?int $limit = null)
    {
        $this->limit = $limit;
    }

    public function format(Value $value)
    {
        $structure = $this->get_block_structure((array)$value->get_value());

        return $value->with_value(
            ac_helper()->html->more(
                $structure,
                $this->limit ?: false,
                '<br>'
            )
        );
    }

    private function get_block_structure(array $blocks, array $values = [], string $prefix = '')
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