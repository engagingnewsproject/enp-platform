<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post;

use AC;
use ACP\Sorting\Model\QueryBindings;

class LastModifiedAuthorFactory
{

    public function create(string $type): ?QueryBindings
    {
        switch ($type) {
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_FIRST_NAME :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_LAST_NAME :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_NICKNAME :
                return new RelatedMeta\User\Meta($type, '_edit_last');
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_LOGIN :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_NICENAME :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_EMAIL :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_ID :
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_DISPLAY_NAME :
                return new RelatedMeta\User\Field($type, '_edit_last');
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_FULL_NAME :
                return new RelatedMeta\User\Meta('last_name', '_edit_last');
            case AC\Setting\ComponentFactory\UserProperty::PROPERTY_ROLES :
            default:
                return null;
        }
    }

}