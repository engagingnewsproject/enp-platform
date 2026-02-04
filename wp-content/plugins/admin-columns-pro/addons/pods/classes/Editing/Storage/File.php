<?php

declare(strict_types=1);

namespace ACA\Pods\Editing\Storage;

use AC\MetaType;
use ACA\Pods;

class File extends Field
{

    public function __construct(Pods\Field $field, MetaType $meta_type)
    {
        parent::__construct(
            $field,
            new Read\DbRaw($field->get_name(), $meta_type)
        );
    }

    public function update(int $id, $data): bool
    {
        $value = [];

        if ( ! empty($data)) {
            foreach ((array)$data as $attachment_id) {
                $value[$attachment_id] = [
                    'id'    => $attachment_id,
                    'title' => get_the_title($attachment_id),
                ];
            }
        }

        return parent::update($id, $value);
    }

}