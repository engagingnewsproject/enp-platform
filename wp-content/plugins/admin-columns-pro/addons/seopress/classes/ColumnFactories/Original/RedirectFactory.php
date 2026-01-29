<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactories\Original;

use AC\PostType;
use AC\TableScreen;
use ACA\SeoPress\ColumnFactory\Redirect;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class RedirectFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('seopress_404')) {
            return [];
        }

        return [
            'seopress_404_redirect_enable'       => Redirect\Original\Enable::class,
            'seopress_404_redirect_value'        => Redirect\Original\Destination::class,
            'seopress_404_redirect_type'         => Redirect\Original\RedirectionType::class,
            'seopress_404_redirect_date_request' => Redirect\Original\LastLoaded::class,
            'seopress_404'                       => Redirect\Original\Hits::class,
        ];
    }

}