<?php

namespace ACP\Search\Comparison\Meta;

use ACP\Helper\Select;

trait UnserializedValuesTrait
{

    private function get_unserialized_values(array $meta_values, array $values = []): array
    {
        foreach ($meta_values as $value) {
            if (is_serialized($value)) {
                $unserialized = unserialize($value, ['allowed_classes' => false]);
                if ($unserialized !== false) {
                    $values = $this->get_unserialized_values($unserialized, $values);
                }

                continue;
            }

            if (is_numeric($value)) {
                $values[] = (int)$value;
            }
        }

        return $values;
    }

}