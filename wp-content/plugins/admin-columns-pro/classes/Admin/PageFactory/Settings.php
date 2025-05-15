<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Asset\Location;
use ACP\Admin\MenuFactory;
use ACP\Admin\Section;
use ACP\Settings\General\LayoutStyle;
use ACP\Sorting\Admin\Section\ResetSorting;

class Settings extends AC\Admin\PageFactory\Settings
{

    private $layout_style;

    public function __construct(
        Location\Absolute $location,
        MenuFactory $menu_factory,
        LayoutStyle $layout_style,
        AC\Settings\General\EditButton $edit_button
    ) {
        parent::__construct($location, $menu_factory, true, $edit_button);

        $this->layout_style = $layout_style;
    }

    public function create(): AC\Admin\Page\Settings
    {
        $page = parent::create();
        $page->add_section(new ResetSorting(), 30);

        $general_section = $page->get_section(AC\Admin\Section\General::NAME);
        if ($general_section instanceof AC\Admin\Section\General) {
            $general_section->add_option(
                new Section\Partial\LayoutTabs($this->layout_style)
            );
        }

        return $page;
    }

}