<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Admin\Page\Columns;
use AC\Asset\Style;
use AC\Registerable;
use ACP\Admin\ScriptFactory\ColumnSettingsFactory;
use ACP\AdminColumnsPro;

class ColumnScripts implements Registerable
{

    private $location;

    private $settings_factory;

    public function __construct(AdminColumnsPro $plugin, ColumnSettingsFactory $settings_factory)
    {
        $this->location = $plugin->get_location();
        $this->settings_factory = $settings_factory;
    }

    public function register(): void
    {
        add_action('ac/admin_scripts', [$this, 'admin_scripts']);
    }

    public function admin_scripts($page): void
    {
        if ( ! $page instanceof Columns) {
            return;
        }

        wp_deregister_script('select2'); // try to remove any other version of select2

        $style = new Style(
            'acp-layouts',
            $this->location->with_suffix('assets/core/css/layouts.css'),
            ['ac-utilities']
        );
        $style->enqueue();

        // Select2
        wp_enqueue_style('ac-select2');
        wp_enqueue_script('ac-select2');

        $this->settings_factory
            ->create($page->get_table_screen())
            ->enqueue();
    }

}