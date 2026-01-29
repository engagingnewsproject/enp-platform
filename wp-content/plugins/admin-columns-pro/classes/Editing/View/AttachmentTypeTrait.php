<?php

namespace ACP\Editing\View;

use ACP\Editing\View;
use InvalidArgumentException;

trait AttachmentTypeTrait
{

    public function set_attachment_type(string $type): View
    {
        if ( ! in_array($type, ['image', 'video', 'audio'], true)) {
            throw new InvalidArgumentException('Invalid attachment type.');
        }

        $args = (array)$this->get_arg('attachment');

        $args['library']['type'] = $type;

        return $this->set('attachment', $args);
    }

}