<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class Slider extends Field\Field implements Field\Numeric
{

    private function has_js_options()
    {
        return isset($this->settings['js-options']);
    }

    private function get_js_option(string $key, $default = null): ?string
    {
        return isset($this->settings['js-options'][$key])
            ? (string)$this->settings['js-options'][$key]
            : $default;
    }

    public function get_min(): ?float
    {
        if ( ! $this->has_js_options()) {
            return 0;
        }

        return (float)$this->get_js_option('min', 0);
    }

    public function get_max(): ?float
    {
        if ( ! $this->has_js_options()) {
            return 100;
        }

        return (float)$this->get_js_option('max', 100);
    }

    public function get_step(): ?string
    {
        return '1';
    }
}