<?php

namespace ACP\Admin\Section\Partial;

use AC\Renderable;
use AC\View;
use ACP\Settings\General;

class LayoutTabs implements Renderable
{

    private $option;

    public function __construct(General\LayoutStyle $option)
    {
        $this->option = $option;
    }

    public function render(): string
    {
        $options = [
            General\LayoutStyle::OPTION_TABS     => _x('Tabs', 'table view display', 'codepress-admin-columns'),
            General\LayoutStyle::OPTION_DROPDOWN => _x('Dropdown', 'table view display', 'codepress-admin-columns'),
        ];

        $setting = new View([
            'options' => json_encode($options),
            'value'   => $this->option->get_style(),
        ]);
        $setting->set_template('admin/settings/layout-style');

        $view = new View(['setting' => $setting->render()]);

        return $view->set_template('admin/settings/setting-row')->render();
    }

}