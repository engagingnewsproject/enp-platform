<?php

namespace ACP\Editing;

use InvalidArgumentException;

class View
{

    protected array $args = [];

    public function __construct(string $type)
    {
        $this->set('type', $type);
    }

    protected function set($key, $value): self
    {
        if ( ! $this->validate($value)) {
            throw new InvalidArgumentException('Invalid value.');
        }

        if (is_array($value)) {
            $value = array_replace((array)$this->get_arg($key), $value);
        }

        $this->args[$key] = $value;

        return $this;
    }

    public function get_arg($key)
    {
        return $this->args[$key] ?? null;
    }

    private function validate($value): bool
    {
        return is_array($value) || is_scalar($value);
    }

    public function set_clear_button(bool $enable): View
    {
        $this->set('clear_button', $enable);

        return $this;
    }

    public function set_required(bool $required): self
    {
        $this->set('required', $required);

        return $this;
    }

    public function set_revisioning(bool $enable): self
    {
        $this->set('disable_revisioning', ! $enable);

        return $this;
    }

    public function set_js_selector(string $selector): self
    {
        $this->set('js', [
            'selector' => $selector,
        ]);

        return $this;
    }

    public function get_args(): array
    {
        return $this->args;
    }

}