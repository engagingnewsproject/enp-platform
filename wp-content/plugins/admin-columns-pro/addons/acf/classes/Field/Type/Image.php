<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Image extends Field implements Field\Library
{

    public function is_upload_media_only(): bool
    {
        return isset($this->settings['library']) && 'uploadedTo' === $this->settings['library'];
    }

}