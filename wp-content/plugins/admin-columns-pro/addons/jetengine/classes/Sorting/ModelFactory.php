<?php

declare(strict_types=1);

namespace ACA\JetEngine\Sorting;

use AC\Type\TableScreenContext;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\Type;
use ACA\JetEngine\Sorting;
use ACP;
use ACP\Sorting\Type\DataType;

final class ModelFactory
{

    private ACP\Sorting\Model\MetaFormatFactory $meta_format_factory;

    private ACP\Sorting\Model\MetaFactory $meta_factory;

    private ACP\Sorting\Model\MetaMappingFactory $meta_mapping_factory;

    public function __construct(
        ACP\Sorting\Model\MetaFormatFactory $meta_format_factory,
        ACP\Sorting\Model\MetaFactory $meta_factory,
        ACP\Sorting\Model\MetaMappingFactory $meta_mapping_factory
    ) {
        $this->meta_format_factory = $meta_format_factory;
        $this->meta_factory = $meta_factory;
        $this->meta_mapping_factory = $meta_mapping_factory;
    }

    public function create(Field $field, TableScreenContext $context)
    {
        $args = [
            'post_type' => $context->has_post_type() ? (string)$context->get_post_type() : null,
            'taxonomy'  => $context->has_taxonomy() ? (string)$context->get_taxonomy() : null,
        ];

        switch (true) {
            case $field instanceof Type\Media:
                return $this->meta_format_factory->create(
                    $context->get_meta_type(),
                    $field->get_name(),
                    new Sorting\FormatValue\Media(),
                    null,
                    $args
                );

            case $field instanceof Type\Select:
                $choices = $field->get_options();

                natcasesort($choices);

                return $field->is_multiple()
                    ? $this->meta_format_factory->create(
                        $context->get_meta_type(),
                        $field->get_name(),
                        new FormatValue\Select($choices),
                        null,
                        $args
                    )
                    : $this->meta_mapping_factory->create(
                        (string)$context->get_meta_type(),
                        $field->get_name(),
                        array_keys($choices)
                    );

            case $field instanceof Type\Date:
                return $this->meta_factory->create(
                    $context->get_meta_type(),
                    $field->get_name(),
                    $field->is_timestamp()
                        ? DataType::create_numeric()
                        : DataType::create_date()
                );

            case $field instanceof Type\DateTime:
                return $this->meta_factory->create(
                    $context->get_meta_type(),
                    $field->get_name(),
                    $field->is_timestamp()
                        ? DataType::create_numeric()
                        : DataType::create_date_time()

                );
            case $field instanceof Type\Posts:
                return null;

            case $field instanceof Type\Number:
                return $this->meta_factory->create(
                    $context->get_meta_type(),
                    $field->get_name(),
                    DataType::create_numeric()
                );

            case $field instanceof Type\Checkbox:
            case $field instanceof Type\Gallery:
                return null;

            default:
                return $this->meta_factory->create(
                    $context->get_meta_type(),
                    $field->get_name()
                );
        }
    }

}