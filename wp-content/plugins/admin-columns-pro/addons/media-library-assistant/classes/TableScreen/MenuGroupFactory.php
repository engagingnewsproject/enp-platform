<?php

declare(strict_types=1);

namespace ACA\MLA\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ($table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return new MenuGroup('mla', __('Media Library Assistant'), 20);
        }

        return null;
    }

}