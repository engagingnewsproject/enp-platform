<?php

namespace ACP\Editing\Service;

use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;
use DateTime;

class Date implements Service
{

    public const FORMAT = 'Y-m-d';

    private View\Date $view;

    private Storage $storage;

    protected string $date_format;

    public function __construct(View\Date $view, Storage $storage, $date_format = self::FORMAT)
    {
        $this->view = $view;
        $this->storage = $storage;
        $this->date_format = (string)$date_format;
    }

    public function get_view(string $context): ?View
    {
        return $this->view;
    }

    public function get_value(int $id)
    {
        $value = DateTime::createFromFormat($this->date_format, $this->storage->get($id));

        return $value
            ? $value->format(self::FORMAT)
            : false;
    }

    public function update(int $id, $data): void
    {
        $value = $data;

        if ($value) {
            $timestamp = strtotime($value);
            $date_time = $timestamp ? DateTime::createFromFormat('U', $timestamp) : null;

            $value = $date_time
                ? $date_time->format($this->date_format)
                : false;
        }

        $this->storage->update($id, $value);
    }

}