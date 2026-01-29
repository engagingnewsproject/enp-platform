<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Sorting;

class AdminColorScheme extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    protected const META_KEY = 'admin_color';

    public function get_column_type(): string
    {
        return 'column-color-scheme';
    }

    public function get_label(): string
    {
        return __('Admin Color Scheme', 'codepress-admin-columns');
    }

    private function get_color_schemes(): array
    {
        global $_wp_admin_css_colors;

        $values = [];

        foreach ($_wp_admin_css_colors as $key => $admin_css_color) {
            $values[$key] = $admin_css_color->name;
        }

        return $values;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\User\Meta(self::META_KEY),
            new AC\Formatter\MapOptionLabel($this->get_color_schemes()),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Select($this->get_color_schemes()),
            new Editing\Storage\User\Meta(self::META_KEY)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $choices = $this->get_color_schemes();
        natcasesort($choices);

        return (new Sorting\Model\MetaMappingFactory())->create('user', self::META_KEY, $choices);
    }

}