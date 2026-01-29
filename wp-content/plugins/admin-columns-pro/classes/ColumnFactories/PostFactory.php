<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\Collection;
use AC\ColumnFactoryDefinitionCollection;
use AC\PostType;
use AC\TableScreen;
use ACP\ColumnFactory\CustomFieldFactory;
use ACP\ColumnFactory\Post;

class PostFactory extends AC\ColumnFactories\BaseFactory
{

    protected const FACTORY = 'column_factory';

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof PostType) {
            return $collection;
        }

        $post_type = (string)$table_screen->get_post_type();
        $free_factory = $this->container->get(AC\ColumnFactories\PostFactory::class);
        $free_factories = $free_factory->create($table_screen);

        $factories = [
            CustomFieldFactory::class,
            Post\Ancestor::class            => [
                self::FACTORY => AC\ColumnFactory\Post\AttachmentFactory::class,
            ],
            Post\Attachment::class,
            Post\AuthorName::class,
            Post\BeforeMoreTag::class       => [
                self::FACTORY => AC\ColumnFactory\Post\BeforeMoreFactory::class,
            ],
            Post\ChildPages::class,
            Post\CommentStatus::class       => [
                self::FACTORY => AC\ColumnFactory\Post\CommentStatusFactory::class,
            ],
            Post\CommentCount::class        => [
                self::FACTORY => AC\ColumnFactory\Post\CommentCountFactory::class,
            ],
            Post\Content::class             => [
                self::FACTORY => AC\ColumnFactory\Post\ContentFactory::class,
            ],
            Post\DatePublished::class       => [
                self::FACTORY => AC\ColumnFactory\Post\DatePublishFactory::class,
            ],
            Post\Discussion::class          => [
                self::FACTORY => AC\ColumnFactory\Post\DiscussionFactory::class,
            ],
            Post\Depth::class,
            Post\EstimateReadingTime::class => [
                self::FACTORY => AC\ColumnFactory\Post\EstimateReadingTimeFactory::class,
            ],
            Post\GutenbergBlocks::class,
            Post\HasTerm::class,
            Post\Id::class                  => [
                self::FACTORY => AC\ColumnFactory\Post\IdFactory::class,
            ],
            Post\Images::class,
            Post\LastModifiedAuthor::class,
            Post\LastModifiedDate::class    => [
                self::FACTORY => AC\ColumnFactory\Post\LastModifiedFactory::class,
            ],
            Post\LatestComment::class,
            Post\LinkCount::class,
            Post\Menu::class                => [
                self::FACTORY => AC\ColumnFactory\Post\MenuFactory::class,
            ],
            Post\PageTemplate::class        => [
                self::FACTORY => AC\ColumnFactory\Post\PageTemplateFactory::class,
            ],
            Post\PasswordProtected::class   => [
                self::FACTORY => AC\ColumnFactory\Post\PasswordProtectedFactory::class,
            ],
            Post\Path::class                => [
                self::FACTORY => AC\ColumnFactory\Post\PathFactory::class,
            ],
            Post\Permalink::class           => [
                self::FACTORY => AC\ColumnFactory\Post\PermalinkFactory::class,
            ],
            Post\PingStatus::class          => [
                self::FACTORY => AC\ColumnFactory\Post\PingStatusFactory::class,
            ],
            Post\PostParent::class          => [
                self::FACTORY => AC\ColumnFactory\Post\ParentFactory::class,
            ],
            Post\PostType::class,
            Post\PostVisibility::class,
            Post\Order::class,
            Post\Revisions::class,
            Post\ShortLink::class           => [
                self::FACTORY => AC\ColumnFactory\Post\ShortLinkFactory::class,
            ],
            Post\Shortcode::class,
            Post\Shortcodes::class          => [
                self::FACTORY => AC\ColumnFactory\Post\ShortcodesFactory::class,
            ],
            Post\Slug::class                => [
                self::FACTORY => AC\ColumnFactory\Post\SlugFactory::class,
            ],
            Post\Status::class              => [
                self::FACTORY => AC\ColumnFactory\Post\StatusFactory::class,
            ],
            Post\Taxonomy::class            => [
                self::FACTORY => AC\ColumnFactory\Post\TaxonomyFactory::class,
            ],
            Post\WordCount::class,
        ];

        if (post_type_supports($post_type, 'comments')) {
            $factories[Post\CommentCount::class] = [
                self::FACTORY => AC\ColumnFactory\Post\CommentCountFactory::class,
            ];
        }

        if (post_type_supports($post_type, 'title')) {
            $factories[Post\TitleRaw::class] = [
                self::FACTORY => AC\ColumnFactory\Post\TitleRawFactory::class,
            ];
        }

        if (post_type_supports($post_type, 'thumbnail')) {
            $factories[Post\FeaturedImage::class] = [
                self::FACTORY => AC\ColumnFactory\Post\FeaturedImageFactory::class,
            ];
        }

        if (post_type_supports($post_type, 'post-formats')) {
            $factories[Post\Formats::class] = [
                self::FACTORY => AC\ColumnFactory\Post\FormatsFactory::class,
            ];
        }

        if (count(ac_helper()->taxonomy->get_taxonomy_selection_options($post_type)) > 0) {
            $factories[Post\Taxonomy::class] = [
                self::FACTORY => AC\ColumnFactory\Post\TaxonomyFactory::class,
            ];
        }

        if (post_type_supports($post_type, 'excerpt')) {
            $factories[Post\Excerpt::class] = [
                self::FACTORY => AC\ColumnFactory\Post\ExcerptFactory::class,
            ];
        }

        if ('post' === $post_type) {
            $factories[Post\Sticky::class] = [
                self::FACTORY => AC\ColumnFactory\Post\StickyFactory::class,
            ];
        }

        foreach ($factories as $class => $args) {
            if (is_numeric($class)) {
                $class = $args;
                $args = [];
            }

            if (array_key_exists(self::FACTORY, $args)) {
                $args[self::FACTORY] = $this->find_free_factory(
                    $free_factories,
                    $args[self::FACTORY]
                );

                if ( ! $args[self::FACTORY]) {
                    continue;
                }
            }

            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    $class, $args
                )
            );
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