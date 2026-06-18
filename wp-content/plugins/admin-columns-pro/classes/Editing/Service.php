<?php

namespace ACP\Editing;

interface Service
{

    public const CONTEXT_SINGLE = 'single';
    public const CONTEXT_BULK = 'bulk';

    public function get_view(string $context): ?View;

    public function get_value(int $id);

    public function update(int $id, $data): void;

}