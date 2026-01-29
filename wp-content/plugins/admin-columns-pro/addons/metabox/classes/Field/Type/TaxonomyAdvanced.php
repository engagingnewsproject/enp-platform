<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class TaxonomyAdvanced extends Field\Field implements Field\Placeholder, Field\Multiple, Field\SelectType
{

    use Field\PlaceholderTrait;
    use Field\SelectTypeTrait;
    use Field\QueryArgsTrait;

    public function get_taxonomies(): array
    {
        return (array)$this->settings['taxonomy'];
    }

    public function is_multiple(): bool
    {
        if ($this->get_select_type() === 'radio_list') {
            return false;
        }

        if (in_array($this->get_select_type(), ['checkbox_list', 'checkbox_list'])) {
            return true;
        }

        // Applies to 'select' and 'select_advanced'
        return $this->check_true_value('multiple');
    }

}