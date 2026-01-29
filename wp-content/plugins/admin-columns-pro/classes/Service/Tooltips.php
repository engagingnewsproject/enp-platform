<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Registerable;
use AC\View;
use ACP\AdminColumnsPro;

class Tooltips implements Registerable
{

    private AdminColumnsPro $plugin;

    public function __construct(AdminColumnsPro $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_filter('ac/page/columns/render', [$this, 'render'], 10, 2);
    }

    private function create_view(string $slug): string
    {
        $view = new View([
            'location' => $this->plugin->get_location(),
        ]);

        return $view->set_template('admin/tooltip/' . $slug)->render();
    }

    public function render(string $html): string
    {
        $tooltips = array_map(
            [
                $this,
                'create_view',
            ],
            [
                'primary-column',
                'preferred-segment',
                'horizontal-scrolling',
                'wrapping',
            ]
        );

        return $html . implode($tooltips);
    }

}