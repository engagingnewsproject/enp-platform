<?php

namespace ACP\Search;

use AC;
use AC\TableScreen\Comment;
use AC\TableScreen\Media;
use AC\TableScreen\Post;
use AC\TableScreen\User;
use ACP\TableScreen\NetworkUser;
use ACP\TableScreen\Taxonomy;

class TableMarkupFactory
{

    private static array $table_markups = [
        Post::class        => TableMarkup\Post::class,
        Media::class       => TableMarkup\Post::class,
        Comment::class     => TableMarkup\Comment::class,
        NetworkUser::class => TableMarkup\MSUser::class,
        User::class        => TableMarkup\User::class,
        Taxonomy::class    => TableMarkup\Taxonomy::class,
    ];

    /**
     * @param string $table_screen        AC\TableScreen class (FQN)
     * @param string $table_screen_search TableScreen class (FQN)
     */
    public static function register(string $table_screen, string $table_screen_search): void
    {
        self::$table_markups[$table_screen] = $table_screen_search;
    }

    public static function create(AC\TableScreen $table_screen, array $assets): ?TableMarkup
    {
        $table_markup_reference = self::get_table_markup_reference($table_screen);

        if ( ! $table_markup_reference) {
            return null;
        }

        $table_markup = new $table_markup_reference($assets);

        return $table_markup instanceof TableMarkup
            ? $table_markup
            : null;
    }

    public static function get_table_markup_reference(AC\TableScreen $table_screen): ?string
    {
        foreach (self::$table_markups as $table_screen_reference => $table_markup_reference) {
            if ($table_screen instanceof $table_screen_reference) {
                return $table_markup_reference;
            }
        }

        return null;
    }

}