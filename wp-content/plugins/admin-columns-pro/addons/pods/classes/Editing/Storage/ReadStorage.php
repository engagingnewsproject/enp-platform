<?php

declare(strict_types=1);

namespace ACA\Pods\Editing\Storage;

interface ReadStorage
{

    /**
     * @return mixed
     */
    public function get(int $id);
}