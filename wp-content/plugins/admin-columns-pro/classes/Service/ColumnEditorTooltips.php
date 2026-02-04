<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Registerable;
use AC\View;

class ColumnEditorTooltips implements Registerable
{

    public function register(): void
    {
        add_filter('ac/page/columns/render', [$this, 'render'], 10, 2);
    }

    private function create_view(string $slug): string
    {
        return (new View())->set_template('tooltip/' . $slug)->render();
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