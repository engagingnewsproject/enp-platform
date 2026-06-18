<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\TableId;
use ACP\ColumnFactory;

class NetworkUsersFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen->get_id()->equals(new TableId('wp-ms_users'))) {
            return $collection;
        }

        $factories = [
            ColumnFactory\CustomFieldFactory::class,
            AC\ColumnFactory\ActionsFactory::class,
            ColumnFactory\User\AdminColorScheme::class,
            ColumnFactory\User\CommentCount::class,
            ColumnFactory\User\Description::class,
            ColumnFactory\User\FirstName::class,
            ColumnFactory\User\FirstPost::class,
            ColumnFactory\User\FullName::class,
            ColumnFactory\User\Gravatar::class,
            ColumnFactory\User\Language::class,
            ColumnFactory\User\LastName::class,
            ColumnFactory\User\LastPost::class,
            ColumnFactory\User\Nickname::class,
            ColumnFactory\User\Password::class,
            ColumnFactory\User\PostCount::class,
            ColumnFactory\User\Registered::class,
            ColumnFactory\User\Roles::class,
            ColumnFactory\User\ShowToolbar::class,
            ColumnFactory\User\UserUrl::class,
            ColumnFactory\User\VisualEditor::class,
            ColumnFactory\User\UserId::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}