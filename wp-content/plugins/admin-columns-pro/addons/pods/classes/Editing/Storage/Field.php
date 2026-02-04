<?php

declare(strict_types=1);

namespace ACA\Pods\Editing\Storage;

use ACA\Pods;
use ACA\Pods\Editing\Storage\Read\PodsRaw;
use ACP;

class Field implements ACP\Editing\Storage
{

    protected ReadStorage $read_storage;

    protected Pods\Field $field;

    public function __construct(Pods\Field $field, ?ReadStorage $read = null)
    {
        $this->field = $field;
        $this->read_storage = $read ?? new PodsRaw($field->get_pod()->get_name(), $field->get_name());
    }

    public function get(int $id)
    {
        return $this->read_storage->get($id);
    }

    public function update(int $id, $data): bool
    {
        $pod = pods($this->field->get_pod(), $id, true);

        return false !== $pod->save([$this->field->get_name() => $data]);
    }

}