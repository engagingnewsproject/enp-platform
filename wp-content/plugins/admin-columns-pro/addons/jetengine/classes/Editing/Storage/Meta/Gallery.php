<?php

declare(strict_types=1);

namespace ACA\JetEngine\Editing\Storage\Meta;

use AC\MetaType;
use ACA\JetEngine\Field\ValueFormat;
use ACA\JetEngine\Mapping\MediaId;
use ACP;

class Gallery extends ACP\Editing\Storage\Meta
{

    private string $value_format;

    public function __construct(string $meta_key, MetaType $meta_type, string $value_format)
    {
        parent::__construct($meta_key, $meta_type);

        $this->value_format = $value_format;
    }

    public function get(int $id)
    {
        $value = parent::get($id);

        if (empty($value)) {
            return false;
        }

        switch ($this->value_format) {
            case ValueFormat::FORMAT_BOTH:
                return array_map([MediaId::class, 'from_array'], (array)$value);

            case ValueFormat::FORMAT_URL:
                $items = explode(',', (string)$value);

                if ( ! $items) {
                    return false;
                }

                $items = array_map([MediaId::class, 'from_url'], $items);

                return array_filter($items, 'is_numeric');
            default:
                $items = explode(',', (string)$value);

                if ( ! $items) {
                    return false;
                }

                return array_filter($items);
        }
    }

    public function update(int $id, $data): bool
    {
        if (empty($data)) {
            return parent::update($id, $data);
        }

        switch ($this->value_format) {
            case ValueFormat::FORMAT_URL:
                $data = implode(',', array_map([MediaId::class, 'to_url'], $data));

                break;
            case ValueFormat::FORMAT_BOTH:
                $data = array_map([MediaId::class, 'to_array'], $data);

                break;
            default:
                $data = implode(',', $data);
        }

        return parent::update($id, $data);
    }

}