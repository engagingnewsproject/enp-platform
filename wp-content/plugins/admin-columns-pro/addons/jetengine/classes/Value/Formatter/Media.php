<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\JetEngine\Field;

class Media implements Formatter
{

    private Field\Field $field;

    public function __construct(Field\Field $field)
    {
        $this->field = $field;
    }

    public function format(Value $value)
    {
        if ( ! $value->get_value()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = $this->get_media_url_by_value($value->get_value());

        $label = $url
            ? ac_helper()->html->link($url, esc_html(basename($url)), ['target' => '_blank'])
            : '<em>' . __('Invalid attachment', 'codepress-admin-columns') . '</em>';

        return $value->with_value($label);
    }

    private function get_media_url_by_value($value): ?string
    {
        $format = $this->field instanceof Field\ValueFormat
            ? $this->field->get_value_format()
            : null;

        switch ($format) {
            case Field\ValueFormat::FORMAT_ID:
                return wp_get_attachment_url((string)$value) ?: null;
            case Field\ValueFormat::FORMAT_BOTH:
                if ( ! is_array($value)) {
                    throw ValueNotFoundException::from_id($value->get_id());
                }

                return $value['url'] ?? null;
            default:
                return (string)$value;
        }
    }

}