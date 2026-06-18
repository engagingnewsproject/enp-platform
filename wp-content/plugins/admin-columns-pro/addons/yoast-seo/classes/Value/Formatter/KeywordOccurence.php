<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Helper;
use AC\Type\Value;

class KeywordOccurence implements AC\Formatter
{

    public function format(Value $value): Value
    {
        $keyword = (string)get_post_meta($value->get_id(), '_yoast_wpseo_focuskw', true);

        $key = strtolower($keyword);

        if ( ! $key) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [
            Helper\Html::create()->tooltip(
                (string)$this->calculate_occurrence(
                    (string)get_post_field('post_title', $value->get_id(), 'raw'),
                    $key
                ),
                __('Title')
            ),
            Helper\Html::create()->tooltip(
                (string)$this->calculate_occurrence(
                    (string)get_post_field('post_content', $value->get_id(), 'raw'),
                    $key
                ),
                __('Content')
            ),
        ];

        return $value->with_value(implode(' / ', $values));
    }

    private function calculate_occurrence(string $content, string $key): int
    {
        return substr_count(strip_tags(strtolower($content)), $key);
    }
}