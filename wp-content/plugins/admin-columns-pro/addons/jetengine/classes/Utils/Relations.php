<?php

declare(strict_types=1);

namespace ACA\JetEngine\Utils;

final class Relations
{

    public const RELATION_ONE_TO_ONE = 'one_to_one';
    public const RELATION_ONE_TO_MANY = 'one_to_many';
    public const RELATION_MANY_TO_MANY = 'many_to_many';

    public static function get_related_post_type(array $relation, string $current_post_type): ?string
    {
        if ( ! $relation || ! isset($relation['post_type_1']) || ! isset($relation['post_type_2'])) {
            return null;
        }

        return $relation['post_type_1'] === $current_post_type
            ? $relation['post_type_2']
            : $relation['post_type_1'];
    }

    public static function has_multiple_relations(array $relation, string $current_post_type): bool
    {
        switch ($relation['type']) {
            case self::RELATION_ONE_TO_MANY:
                return $relation['post_type_1'] === $current_post_type;

            case self::RELATION_MANY_TO_MANY:
                return true;

            case self::RELATION_ONE_TO_ONE:
            default:
                return false;
        }
    }

}