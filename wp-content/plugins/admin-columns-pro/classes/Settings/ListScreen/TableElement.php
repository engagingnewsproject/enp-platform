<?php

namespace ACP\Settings\ListScreen;

use AC\ListScreen;
use InvalidArgumentException;

class TableElement
{

    protected string $name;

    protected string $label;

    private string $group;

    protected ?string $dependent_on;

    private bool $default;

    public function __construct(
        string $name,
        string $label,
        string $group,
        ?string $dependent_on = null,
        bool $default = true
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->group = $group;
        $this->dependent_on = $dependent_on;
        $this->default = $default;

        $this->validate();
    }

    private function validate(): void
    {
        if ( ! in_array($this->group, ['element', 'feature'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid group %s', $this->group));
        }
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    public function get_group(): string
    {
        return $this->group;
    }

    public function is_enabled_by_default(): bool
    {
        return $this->default;
    }

    public function is_enabled(ListScreen $list_screen): bool
    {
        $value = $list_screen->get_preference($this->name);

        // No stored setting will return default value
        if (null === $value) {
            return $this->default;
        }

        return 'on' !== $value;
    }

    public function has_dependent_on(): bool
    {
        return null !== $this->dependent_on;
    }

    public function get_dependent_on(): string
    {
        return $this->dependent_on;
    }

}