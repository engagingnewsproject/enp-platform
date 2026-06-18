<?php

declare(strict_types=1);

namespace ACA\EC\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;

final class Scripts implements Registerable
{

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/admin_scripts', [$this, 'admin_scripts']);
    }

    public function admin_scripts(): void
    {
        $style = new AC\Asset\Style('aca-ec-admin', $this->location->with_suffix('assets/css/admin.css'));
        $style->enqueue();
    }

}