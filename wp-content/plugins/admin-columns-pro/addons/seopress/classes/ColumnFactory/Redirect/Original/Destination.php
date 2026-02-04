<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Destination extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Text(),
            new ACP\Editing\Storage\Post\Meta('_seopress_redirections_value')
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text('_seopress_redirections_value');
    }
}