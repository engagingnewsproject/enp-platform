<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field;

use ACA\GravityForms\Field;

interface Container
{

    /**
     * @return array<Field>
     */
    public function get_sub_fields(): array;

    public function get_sub_field(string $id): ?Field;

}