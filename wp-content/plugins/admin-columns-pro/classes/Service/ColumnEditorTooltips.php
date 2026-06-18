<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\AdminColumns;
use AC\Registerable;
use AC\View;

class ColumnEditorTooltips implements Registerable
{

    private AdminColumns $plugin;

    public function __construct(AdminColumns $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_filter('ac/page/columns/render', [$this, 'render'], 10, 2);
    }

    private function create_view(string $slug): string
    {
        $view = new View();
        $view->set_template('tooltip/' . $slug)
             ->set('url', $this->plugin->get_url());

        return $view->render();
    }

    public function render(string $html): string
    {
        $tooltips = array_map(
            [$this, 'create_view'],
            [
                'bulk-editing',
                'conditional-formatting',
                'export',
                'export-disabled',
                'filtering',
                'inline-editing',
                'smart-filtering',
                'sorting',
                'sorting-include-empty',
            ]
        );

        return $html . implode($tooltips);
    }

}