<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\ACF\Field;

class FlexStructure implements Formatter
{

    private Field\Type\FlexibleContent $field;

    public function __construct(Field\Type\FlexibleContent $field)
    {
        $this->field = $field;
    }

    public function format(Value $value)
    {
        $results = [];
        $labels = $this->get_layout_labels();

        $i = 0;
        while (have_rows($this->field->get_meta_key(), $value->get_id())) {
            the_row();
            $title = $labels[get_row_layout()];
            $acf_layout = $this->get_layout_by_name((string)get_row_layout());

            $title = apply_filters(
                'acf/fields/flexible_content/layout_title',
                $title,
                $this->field->get_settings(),
                $acf_layout,
                $i
            );
            $title = apply_filters(
                "acf/fields/flexible_content/layout_title/key={$this->field->get_hash()}",
                $title,
                $this->field->get_settings(),
                $acf_layout,
                $i
            );
            $title = apply_filters(
                "acf/fields/flexible_content/layout_title/name={$this->field->get_meta_key()}",
                $title,
                $this->field->get_settings(),
                $acf_layout,
                $i
            );

            $results[] = '[ ' . $title . ' ]';
            $i++;
        }

        if (empty($results)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode('<br>', $results));
    }

    private function get_layout_labels(): array
    {
        $labels = [];

        foreach ($this->field->get_layouts() as $layout) {
            $labels[$layout['name']] = $layout['label'];
        }

        return $labels;
    }

    private function get_layout_by_name(string $name): array
    {
        foreach ($this->field->get_layouts() as $layout) {
            if ($name === $layout['name']) {
                return $layout;
            }
        }

        return [];
    }

}