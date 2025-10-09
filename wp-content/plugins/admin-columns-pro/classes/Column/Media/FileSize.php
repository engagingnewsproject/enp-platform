<?php

namespace ACP\Column\Media;

use AC;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Export;
use ACP\Sorting;

/**
 * @since 4.0
 */
class FileSize extends AC\Column\Media\FileSize
    implements Sorting\Sortable, Export\Exportable, ConditionalFormat\Formattable
{

    public function sorting()
    {
        return new Sorting\Model\Media\FileSize();
    }

    public function export()
    {
        return new Export\Model\Value($this);
    }

    public function conditional_format(): ?FormattableConfig
    {
        return new FormattableConfig(
            new ConditionalFormat\Formatter\Media\FileSizeFormatter()
        );
    }

}