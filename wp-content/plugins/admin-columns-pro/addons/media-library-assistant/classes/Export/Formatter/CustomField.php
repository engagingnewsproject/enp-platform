<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use MLACore;
use MLAData;
use WP_Post;

class CustomField implements Formatter
{

    use ExtendedPostTrait;

    private string $column_name;

    public function __construct(string $column_name)
    {
        $this->column_name = $column_name;
    }

    private function get_custom_column(): ?string
    {
        static $custom_columns = null;

        if (null === $custom_columns) {
            $custom_columns = MLACore::mla_custom_field_support('custom_columns');
        }

        return $custom_columns[$this->column_name] ?? null;
    }

    private function is_meta(): bool
    {
        $custom_column = $this->get_custom_column();

        return $custom_column && 0 === strpos($custom_column, 'meta:');
    }

    private function get_custom_values(WP_Post $item): ?array
    {
        $custom_column = $this->get_custom_column();

        if ( ! $custom_column) {
            return null;
        }

        if ($this->is_meta()) {
            $meta_key = substr($custom_column, 5);
            $meta_data = $item->mla_wp_attachment_metadata ?? null;

            if (empty($meta_data)) {
                return null;
            }

            $values = MLAData::mla_find_array_element(
                $meta_key,
                $meta_data,
                'array'
            );

            if (is_scalar($values)) {
                return [$values];
            }

            return (array)$values;
        }

        $post_meta = get_post_meta($item->ID, $custom_column);

        if ( ! is_array($post_meta)) {
            return null;
        }

        return $post_meta;
    }

    private function flatten_value($value): string
    {
        if (is_array($value)) {
            $flatten = ac_helper()->array->implode_recursive(__(', '), $value);

            return sprintf('array( %s )', $flatten);
        }

        if ( ! is_scalar($value)) {
            return '';
        }

        if ($this->is_meta()) {
            return (string)$value;
        }

        return esc_html((string)$value);
    }

    public function format(Value $value)
    {
        $item = $this->get_extended_post((int)$value->get_id());

        $values = $item
            ? $this->get_custom_values($item)
            : null;

        if (empty($values)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $list = array_map([$this, 'flatten_value'], $values);

        if (count($list) > 1) {
            return $value->with_value(sprintf('[%s]', implode('],[', $list)));
        }

        return $value->with_value($list[0]);
    }

}