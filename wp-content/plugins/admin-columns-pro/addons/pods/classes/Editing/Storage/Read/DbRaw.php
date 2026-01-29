<?php

declare(strict_types=1);

namespace ACA\Pods\Editing\Storage\Read;

use AC;
use AC\MetaType;
use ACA\Pods\Editing\Storage\ReadStorage;
use ACA\Pods\Value;

class DbRaw implements ReadStorage
{

    private $meta_key;

    private $meta_type;

    public function __construct(string $meta_key, MetaType $meta_type)
    {
        $this->meta_key = $meta_key;
        $this->meta_type = $meta_type;
    }

    public function get(int $id): array
    {
        $formatter = new Value\Formatter\DbRaw($this->meta_key, $this->meta_type);

        return (array)$formatter->format(new AC\Type\Value($id))->get_value();
    }

}