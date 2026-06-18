<?php

declare(strict_types=1);

namespace ACA\WC\Search\Order;

use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\Config;
use ACA\WC;
use ACP;

class OrderMetaFactory
{

    public function create(Config $config): ?ACP\Search\Comparison
    {
        $meta_key = $config->get('meta_field', '');
        $field_type = $config->get('field_type', 'default');

        switch ($field_type) {
            case FieldType::TYPE_BOOLEAN:
                return new ACP\Search\Comparison\Meta\Checkmark($meta_key);

            case FieldType::TYPE_NON_EMPTY :
                return new ACP\Search\Comparison\Meta\EmptyNotEmpty($meta_key);

            case FieldType::TYPE_IMAGE :
            case FieldType::TYPE_MEDIA :
                return new ACP\Search\Comparison\Meta\Post($meta_key, ['attachment']);

            case FieldType::TYPE_NUMERIC :
                return new ACP\Search\Comparison\Meta\Number($meta_key);

            case FieldType::TYPE_USER :
                return new ACP\Search\Comparison\Meta\User($meta_key);

            case FieldType::TYPE_POST :
            case FieldType::TYPE_COLOR :
            case FieldType::TYPE_TEXT :
            case FieldType::TYPE_URL :
                return new ACP\Search\Comparison\Meta\Text($meta_key);
            case FieldType::TYPE_DATE :
                $date_format = $config->get('date_save_format', '');

                switch ($date_format) {
                    case 'Y-m-d H:i:s':
                    case 'Y-m-d':
                        return new WC\Search\OrderMeta\IsoDate($meta_key);
                    case 'U':
                        return new WC\Search\OrderMeta\Timestamp($meta_key);
                    default:
                        return null;
                }
            case FieldType::TYPE_ARRAY:
            case FieldType::TYPE_COUNT:
                return null;
        }

        return new ACP\Search\Comparison\Meta\Text($meta_key);
    }

}