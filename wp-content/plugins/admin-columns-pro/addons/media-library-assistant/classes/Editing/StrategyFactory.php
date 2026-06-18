<?php

declare(strict_types=1);

namespace ACA\MLA\Editing;

use AC\TableScreen;
use AC\ThirdParty;
use ACP;

class StrategyFactory implements ACP\Editing\StrategyFactory
{

    public function create(TableScreen $table_screen): ?ACP\Editing\Strategy
    {
        if ( ! $table_screen instanceof ThirdParty\MediaLibraryAssistant\TableScreen) {
            return null;
        }

        return new Strategy(get_post_type_object('attachment'));
    }

}