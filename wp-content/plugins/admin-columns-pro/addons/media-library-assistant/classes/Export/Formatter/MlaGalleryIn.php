<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use InvalidArgumentException;

class MlaGalleryIn implements Formatter
{

    use ExtendedPostTrait;
    use FormatPostStatusTrait;
    use UnshiftArrayTrait;

    private string $reference_key;

    public function __construct(string $reference_key)
    {
        $this->reference_key = $reference_key;

        $this->validate();
    }

    private function validate(): void
    {
        if ( ! in_array($this->reference_key, ['mla_galleries', 'galleries'], true)) {
            throw new InvalidArgumentException('Invalid gallery reference key.');
        }
    }

    public function format(Value $value)
    {
        $item = $this->get_extended_post((int)$value->get_id());

        if ($item === null) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $galleries = $item->mla_references[$this->reference_key] ?? [];
        $galleries = $this->shift_element_to_top($galleries, $item->post_parent);

        $values = [];

        foreach ($galleries as $gallery) {
            $parent = $gallery['ID'] === $item->post_parent
                ? sprintf(", %s", __('PARENT', 'media-library-assistant'))
                : '';

            $values[] = sprintf(
                "%1\$s (%2\$s %3\$s%4\$s%5\$s)",
                esc_attr($gallery['post_title']),
                esc_attr($gallery['post_type']),
                $gallery['ID'],
                $this->format_post_status($gallery['post_status']),
                $parent
            );
        }

        return $value->with_value(implode(",\n", $values));
    }

}