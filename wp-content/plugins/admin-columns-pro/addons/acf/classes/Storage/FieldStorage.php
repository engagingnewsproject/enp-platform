<?php

declare(strict_types=1);

namespace ACA\ACF\Storage;

use AC\Type\TableScreenContext;
use ACA\ACF\Utils\AcfId;

class FieldStorage
{

    private TableScreenContext $context;

    public function __construct(TableScreenContext $context)
    {
        $this->context = $context;
    }

    public function get(int $id, string $key)
    {
        return get_field($key, AcfId::get_id($id, $this->context), false);
    }

    public function update(int $id, string $key, $value): bool
    {
        return false !== update_field($key, $value, AcfId::get_id($id, $this->context));
    }

    public function delete(int $id, string $key): bool
    {
        return false !== delete_field($key, AcfId::get_id($id, $this->context));
    }

}