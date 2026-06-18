<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\JetEngine\Mapping\MediaId;

class GalleryIds implements Formatter
{

    private ?string $format;

    public function __construct(?string $format = null)
    {
        $this->format = $format;
    }

    public function format(Value $value): ValueCollection
    {
        switch ($this->format) {
            case 'both':
                $ids = array_map([MediaId::class, 'from_array'], (array)$value->get_value());
                $ids = array_filter($ids);
                break;
            case 'url':
                $ids = explode(',', (string)$value->get_value());

                if ( ! $ids) {
                    throw ValueNotFoundException::from_id($value->get_id());
                }

                $ids = array_map([MediaId::class, 'from_url'], $ids);
                $ids = array_filter($ids);
                break;
            default:
                $ids = explode(',', (string)$value->get_value());

                if ( ! $ids) {
                    throw ValueNotFoundException::from_id($value->get_id());
                }

                $ids = array_filter($ids, 'is_numeric');
        }

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}