<?php

declare(strict_types=1);

namespace ACP\Formatter;

use AC;
use AC\Type\Value;

class RenderShortcode implements AC\Formatter
{

    private $shortcode;

    public function __construct(string $shortcode)
    {
        $this->shortcode = $shortcode;
    }

    public function format(Value $value)
    {
        $rendered = $this->get_rendered_shortcodes($value->get_value());

        return $value->with_value(
            implode('<br>', $rendered)
        );
    }

    private function get_rendered_shortcodes(string $content): array
    {
        $result = [];
        if (has_shortcode($content, $this->shortcode)) {
            preg_match_all("/" . get_shortcode_regex() . "/", $content, $matches);

            foreach ($matches[2] as $index => $match) {
                if ($this->shortcode === $match) {
                    $result[] = do_shortcode($matches[0][$index]);
                }
            }
        }

        return array_filter($result);
    }

}