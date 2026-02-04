<?php

namespace ACP\Sorting\Model\User\RelatedMeta;

use AC\Setting\ComponentFactory\UserProperty;

/**
 * For sorting a user list table on a meta_key that holds a User ID (single).
 */
class UserFactory
{

    public function create(string $user_property, string $meta_key)
    {
        switch ($user_property) {
            case UserProperty::PROPERTY_ID :
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