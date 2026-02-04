<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;

class AltText extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Media\AlternateText();
    }

}