<?php

namespace ACP\Editing\Service;

use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;
use DateTime as PhpDateTime;
use RuntimeException;

class DateTime implements Service
{

    public const FORMAT = 'Y-m-d H:i:s';

    private $view;

    private $storage;

    protected $date_format;

    public function __construct(View\DateTime $view, Storage $storage, ?string $date_format = null)
    {
        $this->view = $view;
        $this->storage = $storage;
        $this->date_format = $date_format ?? self::FORMAT;
    }

    public function get_view(string $context): ?View
    {
        return $this->view;
    }

    public function update(int $id, $data): void
    {
        if ($data) {
            $date_time = PhpDateTime::createFromFormat(self::FORMAT, $data);

            if ( ! $date_time) {
                throw new RuntimeException(__("Invalid date provided"));
            }
            $data = $date_time->format($this->date_format);
        }

        $this->storage->update($id, $data);
    }

    public function get_value(int $id)
    {
        $value = $this->storage->get($id);

        if ( ! $value) {
            return false;
        }

        $date = PhpDateTime::createFromFormat($this->date_format, $value);

        return $date
            ? $date->format(self::FORMAT)
            : false;
    }

}