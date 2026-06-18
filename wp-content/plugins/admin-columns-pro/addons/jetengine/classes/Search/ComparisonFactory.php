<?php

declare(strict_types=1);

namespace ACA\JetEngine\Search;

use AC\Meta\Query;
use AC\Meta\QueryMetaFactory;
use AC\Type\TableScreenContext;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\Type;
use ACA\JetEngine\Search;
use ACP;

final class ComparisonFactory
{

    protected function create_query(TableScreenContext $table_context, string $meta_key): Query
    {
        $factory = new QueryMetaFactory();

        return $table_context->has_post_type()
            ? $factory->create_with_post_type($meta_key, (string)$table_context->get_post_type())
            : $factory->create($meta_key, $table_context->get_meta_type());
    }

    public function create(Field $field, TableScreenContext $table_context)
    {
        switch (true) {
            case $field instanceof Type\Number:
                return new ACP\Search\Comparison\Meta\Number($field->get_name());

            case $field instanceof Type\ColorPicker:
            case $field instanceof Type\Time:
            case $field instanceof Type\Text:
            case $field instanceof Type\Textarea:
            case $field instanceof Type\Wysiwyg:
                return new ACP\Search\Comparison\Meta\Text($field->get_name());

            case $field instanceof Type\Switcher:
                return new ACP\Search\Comparison\Meta\Toggle(
                    $field->get_name(),
                    ['true' => __('On', 'codepress-admin-columns'), 'false' => __('Off', 'codepress-admin-columns')]
                );
            case $field instanceof Type\IconPicker:
                return new ACP\Search\Comparison\Meta\SearchableText(
                    $field->get_name(),
                    $this->create_query($table_context, $field->get_name())
                );

            case $field instanceof Type\Checkbox:
                return new Search\Comparison\Checkbox(
                    $field->get_name(),
                    $field->get_options(),
                    $field->value_is_array()
                );

            case $field instanceof Type\Posts:
                return $field->is_multiple()
                    ? new ACP\Search\Comparison\Meta\Posts($field->get_name(), $field->get_related_post_types() ?: [])
                    : new ACP\Search\Comparison\Meta\Post($field->get_name(), $field->get_related_post_types() ?: []);

            case $field instanceof Type\Media:
                $query_factory = new QueryMetaFactory();

                return new ACP\Search\Comparison\Meta\Media(
                    $field->get_name(),
                    $query_factory->create($field->get_name(), $table_context->get_meta_type())
                );

            case $field instanceof Type\Radio:
                return new ACP\Search\Comparison\Meta\Select($field->get_name(), $field->get_options());

            case $field instanceof Type\Select:
                return $field->is_multiple()
                    ? new ACP\Search\Comparison\Meta\MultiSelect($field->get_name(), $field->get_options())
                    : new ACP\Search\Comparison\Meta\Select($field->get_name(), $field->get_options());

            case $field instanceof Type\Date:
                return $field->is_timestamp()
                    ? new ACP\Search\Comparison\Meta\DateTime\Timestamp(
                        $field->get_name(), $this->create_query($table_context, $field->get_name())
                    )
                    : new ACP\Search\Comparison\Meta\Date(
                        $field->get_name(),
                        $this->create_query($table_context, $field->get_name())
                    );

            case $field instanceof Type\DateTime:
                return $field->is_timestamp()
                    ? new ACP\Search\Comparison\Meta\DateTime\Timestamp(
                        $field->get_name(), $this->create_query($table_context, $field->get_name())
                    ) : null;
        }

        return null;
    }
}