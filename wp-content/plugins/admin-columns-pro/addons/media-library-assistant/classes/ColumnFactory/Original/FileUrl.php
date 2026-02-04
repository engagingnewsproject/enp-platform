<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Formatter\Media\AttachmentUrl;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class FileUrl extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AttachmentUrl());
    }
}