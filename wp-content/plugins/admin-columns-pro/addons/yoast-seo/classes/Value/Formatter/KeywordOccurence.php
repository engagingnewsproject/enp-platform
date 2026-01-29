<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Type\Value;

class KeywordOccurence implements AC\Formatter
{

    public function format(Value $value): Value
    {
        $key = strtolower(get_post_meta($value->get_id(), '_yoast_wpseo_focuskw', true));

        if ( ! $key) {
            return $value->with_value(false);
        }

        $values = [
            ac_helper()->html->tooltip(
                (string)$this->calculate_occurrence(get_post_field('post_title', $value->get_id(), 'raw'), $key),
                __('Title')
            ),
            ac_helper()->html->tooltip(
                (string)$this->calculate_occurrence(get_post_field('post_content', $value->get_id(), 'raw'), $key),
                __('Content')
            ),
        ];

        return $value->with_value(implode(' / ', $values));
    }

    private function calculate_occurrence($content, $key): int
    {
        return substr_count(strip_tags(strtolower($content)), $key);
    }
}