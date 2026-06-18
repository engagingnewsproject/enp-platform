<?php

declare(strict_types=1);

namespace ACP\Filtering;

use AC;
use AC\Registerable;
use AC\TableScreen\Comment;
use AC\TableScreen\Media;
use AC\TableScreen\Post;
use AC\TableScreen\User;
use ACP\TableScreen\NetworkUser;
use ACP\TableScreen\Taxonomy;

class TableScreenFactory
{

    /**
     * @var Registerable[]
     */
    private static array $container_screens = [
        Post::class        => Table\Post::class,
        Media::class       => Table\Post::class,
        Comment::class     => Table\Comment::class,
        NetworkUser::class => Table\MsUser::class,
        User::class        => Table\User::class,
        Taxonomy::class    => Table\Taxonomy::class,
    ];

    public static function register(string $table_screen_fqn, string $container_screen_fqn): void
    {
        self::$container_screens[$table_screen_fqn] = $container_screen_fqn;
    }

    public function create(AC\TableScreen $table_screen, string $column_name): ?Registerable
    {
        foreach (self::$container_screens as $table_screen_reference => $container_screen_fqn) {
            if ($table_screen instanceof $table_screen_reference) {
                return new $container_screen_fqn($column_name);
            }
        }

        return null;
    }
}