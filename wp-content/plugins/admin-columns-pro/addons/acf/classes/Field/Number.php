<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface Number
{

    public function get_step(): string;

    public function get_min(): ?int;

    public function get_max(): ?int;

}