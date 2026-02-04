<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Sorting;

class AspectRatio extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-aspect-ratio';
    }

    public function get_label(): string
    {
        return __('Aspect Ratio', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Media\AspectRatioDecimal(),
            new ACP\Formatter\Media\ReadableAspectRatio(),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Media\AspectRatio();
    }

}