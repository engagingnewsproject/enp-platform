<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface ManualBulkOptions
{

    public function has_manual_bulk_options(): bool;

    public function get_manual_bulk_options(): array;

}