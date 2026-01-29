<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\ACF\Field;

class FlexCount implements Formatter
{

    private Field\Type\FlexibleContent $field;

    public function __construct(Field\Type\FlexibleContent $field)
    {
        $this->field = $field;
    }

    public function format(Value $value): Value
    {
        $values = $value->get_value();

        if (empty($values)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if (empty($this->field->get_layouts())) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $layouts = [];
        $labels = $this->get_layout_labels();

        foreach ($values as $field) {
            if ( ! isset($layouts[$field['acf_fc_layout']])) {
                $layouts[$field['acf_fc_layout']] = [
                    'count' => 1,
                    'label' => $labels[$field['acf_fc_layout']] ?? $field['acf_fc_layout'],
                ];
            } else {
                $layouts[$field['acf_fc_layout']]['count']++;
            }
        }

        $result = array_map(function ($l) {
            return ($l['count'] > 1)
                ? sprintf('%s <span class="ac-rounded">%s</span>', $l['label'], $l['count'])
                : $l['label'];
        }, $layouts);

        return $value->with_value(implode('<br>', $result));
    }

    private function get_layout_labels(): array
    {
        $labels = [];

        foreach ($this->field->get_layouts() as $layout) {
            $labels[$layout['name']] = $layout['label'];
        }

        return $labels;
    }

}