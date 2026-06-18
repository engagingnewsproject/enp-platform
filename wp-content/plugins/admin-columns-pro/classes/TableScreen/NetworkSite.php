<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC\ListTable\TotalItemsTrait;
use AC\MetaType;
use AC\TableScreen;
use AC\Type\Labels;
use AC\Type\TableId;
use AC\Type\Url;

class NetworkSite extends TableScreen implements TableScreen\MetaType, TableScreen\TotalItems
{

    use TotalItemsTrait;

    public function __construct()
    {
        parent::__construct(
            new TableId('wp-ms_sites'),
            'sites-network',
            new Labels(
                __('Network Site'),
                __('Network Sites')
            ),
            new Url\ListTableNetwork('sites.php'),
            null,
            true
        );
    }

    public function get_meta_type(): MetaType
    {
        return new MetaType(MetaType::SITE);
    }

}