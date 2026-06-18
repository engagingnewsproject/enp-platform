<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\Collection;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACP\ColumnFactory\Comment;
use ACP\ColumnFactory\CustomFieldFactory;

class CommentFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Comment) {
            return $collection;
        }

        $free_factory = $this->container->get(AC\ColumnFactories\CommentFactory::class);
        $free_factories = $free_factory->create($table_screen);

        $enhanced_column_mapping = [
            Comment\Agent::class        => AC\ColumnFactory\Comment\AgentFactory::class,
            Comment\Approved::class     => AC\ColumnFactory\Comment\ApprovedFactory::class,
            Comment\AuthorAvatar::class => AC\ColumnFactory\Comment\AuthorAvatarFactory::class,
            Comment\AuthorEmail::class  => AC\ColumnFactory\Comment\AuthorEmailFactory::class,
            Comment\AuthorIp::class     => AC\ColumnFactory\Comment\AuthorIpFactory::class,
            Comment\AuthorName::class   => AC\ColumnFactory\Comment\AuthorNameFactory::class,
            Comment\AuthorUrl::class    => AC\ColumnFactory\Comment\AuthorUrlFactory::class,
            Comment\CommentType::class  => AC\ColumnFactory\Comment\CommentTypeFactory::class,
            Comment\DateGmt::class      => AC\ColumnFactory\Comment\DateGmtFactory::class,
            Comment\Excerpt::class      => AC\ColumnFactory\Comment\ExcerptFactory::class,
            Comment\Id::class           => AC\ColumnFactory\Comment\IdFactory::class,
            Comment\Post::class         => AC\ColumnFactory\Comment\PostFactory::class,
            Comment\ReplyTo::class      => AC\ColumnFactory\Comment\ReplyToFactory::class,
            Comment\Status::class       => AC\ColumnFactory\Comment\StatusFactory::class,
            Comment\User::class         => AC\ColumnFactory\Comment\UserFactory::class,
            Comment\WordCount::class    => AC\ColumnFactory\Comment\WordCountFactory::class,
        ];

        foreach ($enhanced_column_mapping as $factory_class => $mapped_factory_class) {
            $column_factory = $this->find_free_factory(
                $free_factories,
                $mapped_factory_class
            );

            if ( ! $column_factory) {
                continue;
            }

            $collection->add(new AC\Type\ColumnFactoryDefinition($factory_class, [
                'column_factory' => $column_factory,
            ]));
        }

        $factories = [
            CustomFieldFactory::class,
            AC\ColumnFactory\ActionsFactory::class,
            Comment\HasReplies::class,
            Comment\IsReply::class,
            Comment\PostType::class,
        ];

        foreach ($factories as $factory_class) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory_class));
        }

        return $collection;
    }

    private function find_free_factory(Collection\ColumnFactories $factories, string $type): ?AC\Column\ColumnFactory
    {
        foreach ($factories as $factory) {
            if ($factory instanceof $type) {
                return $factory;
            }
        }

        return null;
    }

}