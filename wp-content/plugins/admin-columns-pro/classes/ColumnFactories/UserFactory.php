<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACP\ColumnFactory\CustomFieldFactory;
use ACP\ColumnFactory\User;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return $collection;
        }

        $factories = [
            CustomFieldFactory::class,
            User\AdminColorScheme::class,
            User\AuthorSlug::class,
            User\CommentCount::class,
            User\Description::class,
            User\DisplayName::class,
            User\FirstName::class,
            User\FirstPost::class,
            User\FullName::class,
            User\Gravatar::class,
            User\Language::class,
            User\LastName::class,
            User\LastPost::class,
            User\Nickname::class,
            User\Password::class,
            User\PostCount::class,
            User\Registered::class,
            User\Roles::class,
            User\ShowToolbar::class,
            User\TaxonomyFactory::class,
            User\UserId::class,
            User\UserName::class,
            User\UserUrl::class,
            User\VisualEditor::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    $factory
                )
            );
        }

        return $collection;
    }

}