<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post\RelatedMeta;

use AC\Setting\ComponentFactory\UserProperty;
use ACP\Sorting\Model\Post\Meta;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Type\DataType;

/**
 * For sorting a post list table on a meta_key that holds a User ID (single).
 */
class UserFactory
{

    public function create(string $user_property, string $meta_key): ?QueryBindings
    {
        switch ($user_property) {
            case UserProperty::PROPERTY_ID :
                return new Meta($meta_key, new DataType(DataType::NUMERIC));
            case UserProperty::PROPERTY_LOGIN :
            case UserProperty::PROPERTY_NICENAME :
            case UserProperty::PROPERTY_EMAIL :
            case UserProperty::PROPERTY_DISPLAY_NAME :
                return new User\Field($user_property, $meta_key);
            case UserProperty::PROPERTY_FULL_NAME :
                return new User\Meta('last_name', $meta_key);
            case UserProperty::PROPERTY_LAST_NAME :
            case UserProperty::PROPERTY_FIRST_NAME :
            case UserProperty::PROPERTY_NICKNAME :
                return new User\Meta($user_property, $meta_key);
        }

        return null;
    }
}