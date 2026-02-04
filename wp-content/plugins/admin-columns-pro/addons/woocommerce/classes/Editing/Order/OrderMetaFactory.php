<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Order;

use AC\Helper\Select\Option;
use AC\Setting\ComponentFactory\DateSaveFormat;
use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACA\WC\Editing;
use ACP;

final class OrderMetaFactory
{

    public function create(Config $config): ?ACP\Editing\Service
    {
        $meta_key = $config->get('meta_field', '');
        $field_type = $config->get('field_type', 'default');

        switch ($field_type) {
            case FieldType::TYPE_BOOLEAN:
                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\Toggle(
                        new ToggleOptions(
                            new Option('0', __('False', 'codepress-admin-columns')),
                            new Option('1', __('True', 'codepress-admin-columns'))
                        )
                    ),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_COLOR:
                return new ACP\Editing\Service\Basic(
                    (new ACP\Editing\View\Color())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_DATE:
                $date_format = $config->get('date_save_format', '');

                switch ($date_format) {
                    case DateSaveFormat::FORMAT_UNIX_TIMESTAMP:
                    case DateSaveFormat::FORMAT_DATETIME:
                        return new ACP\Editing\Service\DateTime(
                            (new ACP\Editing\View\DateTime())->set_clear_button(true),
                            new Editing\Storage\Order\OrderMeta($meta_key),
                            $date_format
                        );
                    default :
                        return new ACP\Editing\Service\Date(
                            (new ACP\Editing\View\Date())->set_clear_button(true),
                            new Editing\Storage\Order\OrderMeta($meta_key),
                            $date_format
                        );
                }

            case FieldType::TYPE_IMAGE:
                return new ACP\Editing\Service\Basic(
                    (new ACP\Editing\View\Image())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_MEDIA:
                return new ACP\Editing\Service\Basic(
                    (new ACP\Editing\View\Media())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_URL:
                return new ACP\Editing\Service\Basic(
                    (new ACP\Editing\View\Url())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_NUMERIC:
                return new ACP\Editing\Service\ComputedNumber(
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
            case FieldType::TYPE_POST:
                return new ACP\Editing\Service\Post(
                    (new ACP\Editing\View\AjaxSelect())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key),
                    new ACP\Editing\PaginatedOptions\Posts()
                );
            case FieldType::TYPE_USER:
                return new ACP\Editing\Service\User(
                    (new ACP\Editing\View\AjaxSelect())->set_clear_button(true),
                    new Editing\Storage\Order\OrderMeta($meta_key),
                    new ACP\Editing\PaginatedOptions\Users()
                );

            case FieldType::TYPE_COUNT:
            case FieldType::TYPE_NON_EMPTY:
            case FieldType::TYPE_ARRAY:
                return null;
            default:
                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\Text(),
                    new Editing\Storage\Order\OrderMeta($meta_key)
                );
        }
    }

}