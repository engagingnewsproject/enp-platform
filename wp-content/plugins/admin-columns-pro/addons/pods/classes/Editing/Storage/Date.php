<?php

declare(strict_types=1);

namespace ACA\Pods\Editing\Storage;

use ACA\Pods;

class Date extends Field
{

    private string $date_format;

    public function __construct(Pods\Field $field, ReadStorage $read, string $date_format)
    {
        parent::__construct($field, $read);

        $this->date_format = $date_format;
    }

    public function get(int $id)
    {
        $value = parent::get($id);

        return in_array($value, ['0000-00-00', '0000-00-00 00:00:00'])
            ? false
            : $value;
    }

    public function update(int $id, $data): bool
    {
        // There seems to be an exception on how the date is stored
        if ('y' === $this->date_format && $data) {
            $data .= ' 00:00:00';
        }

        return parent::update($id, $data);
    }

}