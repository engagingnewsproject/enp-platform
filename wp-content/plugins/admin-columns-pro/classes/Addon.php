<?php

declare(strict_types=1);

namespace ACP;

use AC\Registerable;

interface Addon extends Registerable
{

    public function get_id(): string;

}