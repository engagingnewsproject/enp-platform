<?php

declare(strict_types=1);

namespace ACA\EC;

class AdditionalField
{

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get_id(): string
    {
        return (string)$this->data['name'];
    }

    public function get_label(): string
    {
        return (string)$this->data['label'];
    }

    public function get_type(): string
    {
        return (string)$this->data['type'];
    }

    public function get_values(): string
    {
        return (string)$this->data['values'];
    }

    public function get_select_options(): array
    {
        $options = explode("\r\n", $this->get_values());

        return $options ? array_combine($options, $options) : [];
    }
}
