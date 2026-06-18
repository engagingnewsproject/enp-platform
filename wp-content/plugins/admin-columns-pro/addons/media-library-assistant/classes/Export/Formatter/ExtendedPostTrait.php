<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use MLAData;
use WP_Post;

trait ExtendedPostTrait
{

    public function get_extended_post(int $id): ?WP_Post
    {
        $data = MLAData::mla_get_attachment_by_id($id);

        return $data
            ? new WP_Post((object)$data)
            : null;
    }

}