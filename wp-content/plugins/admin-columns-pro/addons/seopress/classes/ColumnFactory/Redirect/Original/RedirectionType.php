<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;

class RedirectionType extends OriginalColumnFactory
{

    private function get_redirection_types(): array
    {
        return [
            '301' => __('301 Moved Permanently', 'wp-seopress-pro'),
            '302' => __('302 Found / Moved Temporarily', 'wp-seopress-pro'),
            '307' => __('307 Moved Temporarily', 'wp-seopress-pro'),
            '410' => __('410 Gone', 'wp-seopress-pro'),
            '451' => __('451 Unavailable For Legal Reasons', 'wp-seopress-pro'),
        ];
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_redirection_types()),
            new ACP\Editing\Storage\Post\Meta('_seopress_redirections_type')
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select('_seopress_redirections_type', $this->get_redirection_types());
    }
}