<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface NumberInput
{

    public function has_step(): bool;

    public function get_step(): string;

    public function has_min_value(): bool;

    public function get_min_value(): string;

    public function has_max_value(): bool;

    public function get_max_value(): string;

}