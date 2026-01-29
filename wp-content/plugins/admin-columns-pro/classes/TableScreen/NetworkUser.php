<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC;
use AC\ListTableFactory;
use AC\MetaType;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use AC\Type\Labels;
use AC\Type\TableId;

class NetworkUser extends TableScreen implements ListTable, TableScreen\MetaType, TableScreen\TotalItems
{

    use AC\ListTable\TotalItemsTrait;

    public function __construct()
    {
        parent::__construct(
            new TableId('wp-ms_users'),
            'users-network',
            new Labels(
                __('Network User'),
                __('Network Users')
            ),
            new AC\Type\Url\ListTableNetwork('users.php'),
            null,
            true
        );
    }

    public function get_meta_type(): MetaType
    {
        return new MetaType(MetaType::USER);
    }

    public function list_table(): AC\ListTable
    {
        return ListTableFactory::create_network_user($this->screen_id);
    }

}