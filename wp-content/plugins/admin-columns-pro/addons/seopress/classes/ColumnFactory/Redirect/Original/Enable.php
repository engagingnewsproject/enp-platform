<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect\Original;

use AC\Helper\Select\Option;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACA\SeoPress\Search;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Enable extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Toggle(
                new ToggleOptions(
                    new Option('', __('False', 'codepress-admin-columns')),
                    new Option('yes', __('True', 'codepress-admin-columns'))
                )
            ),
            new ACP\Editing\Storage\Post\Meta('_seopress_redirections_enabled')
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Redirect\Enabled();
    }
}