<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait MediaUploadToTrait
{

    public function set_upload_media_only(bool $upload_only): View
    {
        $args = (array)$this->get_arg('attachment');

        if ($upload_only) {
            $args['library']['uploadedTo'] = true;
        }

        return $this->set('attachment', $args);
    }

}