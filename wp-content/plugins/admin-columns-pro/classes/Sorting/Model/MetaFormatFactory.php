<?php

namespace ACP\Sorting\Model;

use AC\MetaType;
use ACP\Sorting\FormatValue;
use ACP\Sorting\Type\DataType;
use InvalidArgumentException;

class MetaFormatFactory
{

    public function create(
        MetaType $meta_type,
        string $meta_key,
        FormatValue $formatter,
        ?DataType $data_type = null,
        array $args = []
    ) {
        switch ((string)$meta_type) {
            case MetaType::POST :
                return new Post\MetaFormat($formatter, $meta_key, $data_type);
            case MetaType::USER :
                return new User\MetaFormat($formatter, $meta_key, $data_type);
            case MetaType::COMMENT :
                return new Comment\MetaFormat($formatter, $meta_key, $data_type);
            case MetaType::TERM :
                $taxonomy = $args['taxonomy'] ?? null;

                if ( ! $taxonomy) {
                    throw new InvalidArgumentException('Missing taxonomy');
                }

                return new Taxonomy\MetaFormat($taxonomy, $formatter, $meta_key, $data_type);
            default :
                return null;
        }
    }

}