<?php

declare(strict_types=1);

namespace ACA\Types\Editing\Storage;

class RepeatableDate extends Repeater
{

    public function get(int $id): array
    {
        return array_map([$this, 'time_to_date'], array_filter(parent::get($id)));
    }

    public function update(int $id, $data): bool
    {
        $data = is_array($data) ? array_map('strtotime', $data) : false;

        return parent::update($id, $data);
    }

    private function time_to_date($timestamp)
    {
        return date('Y-m-d', (int)$timestamp);
    }

}