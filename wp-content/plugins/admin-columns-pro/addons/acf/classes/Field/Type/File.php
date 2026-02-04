<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class File extends Field implements Field\File
{

    public function get_mime_types(): array
    {
        $mime_types = $this->settings['mime_types'] ?? null;

        if ($mime_types && is_string($mime_types)) {
            return explode(',', $this->settings['mime_types']) ?: [];
        }

        return [];
    }

}