<?php

namespace ACP\Search;

use AC\Asset\Enqueueable;
use AC\Registerable;

class Settings implements Registerable
{

    /**
     * @var Enqueueable[]
     */
    protected array $assets;

    public function __construct(array $assets)
    {
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_action('ac/admin_scripts/columns', [$this, 'admin_scripts']);
    }

    public function admin_scripts()
    {
        foreach ($this->assets as $asset) {
            $asset->enqueue();
        }
    }

}