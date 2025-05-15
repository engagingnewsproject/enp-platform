<?php

namespace ACP\ListScreen;

use AC;
use AC\ColumnRepository;
use AC\ListScreen\ListTable;
use AC\ListScreen\ManageValue;
use AC\MetaType;
use AC\Type\Uri;
use AC\Type\Url;
use AC\Type\Url\EditorNetworkColumns;
use AC\WpListTableFactory;
use ACP\Column;
use ACP\Editing;
use ACP\Export;
use ACP\Sorting;

class MSUser extends AC\ListScreen implements Sorting\ListScreen, Editing\ListScreen, Export\ListScreen,
                                              ManageValue,
                                              ListTable
{

    public function __construct()
    {
        parent::__construct('wp-ms_users', 'users-network');

        $this->label = __('Network Users');
        $this->singular_label = __('Network User');
        $this->group = 'network';
        $this->meta_type = MetaType::USER;
    }

    public function sorting(Sorting\AbstractModel $model): Sorting\Strategy
    {
        return new Sorting\Strategy\User($model);
    }

    public function editing()
    {
        return new Editing\Strategy\User();
    }

    public function export()
    {
        return new Export\Strategy\User($this);
    }

    public function manage_value(): AC\Table\ManageValue
    {
        return new AC\Table\ManageValue\User(new ColumnRepository($this));
    }

    public function list_table(): AC\ListTable
    {
        return new AC\ListTable\NetworkUser(
            (new WpListTableFactory())->create_network_user_table($this->get_screen_id())
        );
    }

    public function get_editor_url(): Uri
    {
        return new EditorNetworkColumns($this->key, $this->has_id() ? $this->get_id() : null);
    }

    public function get_table_url(): Uri
    {
        return new Url\ListTableNetwork('users.php', $this->has_id() ? $this->get_id() : null);
    }

    protected function register_column_types(): void
    {
        $this->register_column_types_from_list([
            Column\CustomField::class,
            Column\Actions::class,
            Column\User\CommentCount::class,
            Column\User\Description::class,
            Column\User\DisplayName::class,
            Column\User\Email::class,
            Column\User\FirstName::class,
            Column\User\FirstPost::class,
            Column\User\FullName::class,
            Column\User\ID::class,
            Column\User\LastName::class,
            Column\User\LastPost::class,
            Column\User\Login::class,
            Column\User\Name::class,
            Column\User\Nicename::class,
            Column\User\Nickname::class,
            Column\User\PostCount::class,
            Column\User\Posts::class,
            Column\User\Registered::class,
            Column\User\RichEditing::class,
            Column\User\Role::class,
            Column\User\ShowToolbar::class,
            Column\User\Url::class,
            Column\User\Username::class,
            Column\NetworkUser\Blogs::class,
        ]);
    }

}