<?php

declare(strict_types=1);

use AC\Plugin\Version;

function ACP(): ACP\AdminColumnsPro
{
    static $acp = null;

    if ($acp === null) {
        $acp = new ACP\AdminColumnsPro(ACP_FILE, new Version(ACP_VERSION));
    }

    return $acp;
}