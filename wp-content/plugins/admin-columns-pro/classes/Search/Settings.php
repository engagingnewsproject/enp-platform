<?php

namespace ACP\Search;

use AC;
use AC\Asset\Enqueueable;
use AC\Registerable;

class Settings implements Registerable
{

    /**
     * @var Enqueueable
     */
    protected $assets;

    public function __construct(array $assets)
    {
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_action('ac/column/settings', [$this, 'column_settings']);
        add_action('ac/admin_scripts/columns', [$this, 'admin_scripts']);
    }

    public function column_settings(AC\Column $column)
    {
        if ( ! $column instanceof Searchable || ! $column->search()) {
            return;
        }

        $setting = new Settings\Column($column);
        $setting->set_default('on');

        $column->add_setting($setting);
    }

    public function admin_scripts()
    {
        foreach ($this->assets as $asset) {
            $asset->enqueue();
        }
    }

}