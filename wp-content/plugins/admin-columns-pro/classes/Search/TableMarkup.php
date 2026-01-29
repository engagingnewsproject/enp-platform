<?php

namespace ACP\Search;

use AC\Asset\Enqueueable;
use AC\Registerable;

abstract class TableMarkup implements Registerable
{

    /**
     * @var Enqueueable[]
     */
    protected $assets;

    public function __construct(array $assets)
    {
        $this->assets = $assets;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'scripts']);
    }

    public function scripts()
    {
        foreach ($this->assets as $asset) {
            $asset->enqueue();
        }

        wp_enqueue_style('wp-pointer');
    }

    public function filters_markup()
    {
        ?>

		<div id="ac-s"></div>

        <?php
    }

}