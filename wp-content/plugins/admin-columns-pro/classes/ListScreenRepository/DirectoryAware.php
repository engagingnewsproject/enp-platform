<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use ACP\Storage\Directory;

interface DirectoryAware
{

    public function get_directory(): Directory;

}