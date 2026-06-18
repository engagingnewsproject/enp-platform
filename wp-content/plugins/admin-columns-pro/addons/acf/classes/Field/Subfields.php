<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

use ACA\ACF\Field;

interface Subfields
{

    public function get_sub_fields(): array;

    public function get_sub_field(string $key): ?Field;

}