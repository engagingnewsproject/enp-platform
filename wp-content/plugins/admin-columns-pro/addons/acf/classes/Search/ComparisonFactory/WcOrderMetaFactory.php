<?php

declare(strict_types=1);

namespace ACA\ACF\Search\ComparisonFactory;

use ACA\ACF;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\WC;
use ACP;
use ACP\Search\Comparison\Meta\Post;

class WcOrderMetaFactory
{

    public function create(Field $field): ?ACP\Search\Comparison
    {
        $meta_key = $field->get_meta_key();
        $field_type = $field->get_type();

        switch ($field_type) {
            case FieldType::TYPE_BOOLEAN:
                return new ACP\Search\Comparison\Meta\Checkmark($meta_key);
            case FieldType::TYPE_BUTTON_GROUP:
                return new ACF\Search\Comparison\Select(
                    $meta_key,
                    $field instanceof Field\Choices ? $field->get_choices() : []
                );

            case FieldType::TYPE_CHECKBOX:
                return new ACF\Search\Comparison\MultiSelect(
                    $meta_key,
                    $field instanceof Field\Choices ? $field->get_choices() : []
                );
            case FieldType::TYPE_IMAGE :
                return new ACP\Search\Comparison\Meta\Image($meta_key);
            case FieldType::TYPE_FILE:
                return new ACP\Search\Comparison\Meta\Post($meta_key, ['attachment']);

            case FieldType::TYPE_GALLERY:
                return new ACP\Search\Comparison\Meta\EmptyNotEmpty($meta_key);

            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_RANGE:
                return new ACP\Search\Comparison\Meta\Decimal($meta_key);

            case FieldType::TYPE_COLOR_PICKER :
            case FieldType::TYPE_EMAIL :
            case FieldType::TYPE_PASSWORD :
            case FieldType::TYPE_TEXTAREA :
            case FieldType::TYPE_TEXT :
            case FieldType::TYPE_URL :
            case FieldType::TYPE_WYSIWYG :
            case FieldType::TYPE_TIME_PICKER :
            case FieldType::TYPE_OEMBED :
                return new ACP\Search\Comparison\Meta\Text($meta_key);
            case FieldType::TYPE_DATE_TIME_PICKER :
                return new WC\Search\OrderMeta\IsoDate($meta_key);
            case FieldType::TYPE_DATE_PICKER :
                return new WC\Search\OrderMeta\AcfDate($meta_key);
            case FieldType::TYPE_SELECT:
            case FieldType::TYPE_RADIO:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];

                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new ACF\Search\Comparison\MultiSelect($meta_key, $choices)
                    : new ACP\Search\Comparison\Meta\Select($meta_key, $choices);

            case FieldType::TYPE_POST:
            case FieldType::TYPE_RELATIONSHIP:
            case FieldType::TYPE_PAGE_LINK:
                $post_types = $field instanceof Field\PostTypeFilterable
                    ? $field->get_post_types()
                    : [];
                $terms = $field instanceof Field\TaxonomyFilterable
                    ? $field->get_taxonomies()
                    : [];

                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new ACP\Search\Comparison\Meta\Posts(
                        $meta_key,
                        $post_types,
                        $terms
                    )
                    : new Post(
                        $meta_key,
                        $post_types,
                        $terms
                    );
            case FieldType::TYPE_TAXONOMY:
                if ( ! $field instanceof Field\Type\Taxonomy || $field->uses_native_term_relation()) {
                    return null;
                }

                return $field->is_multiple()
                    ? new ACF\Search\Comparison\Taxonomies($meta_key, $field->get_taxonomy())
                    : new ACF\Search\Comparison\Taxonomy($meta_key, $field->get_taxonomy());

            case FieldType::TYPE_USER:
                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new ACF\Search\Comparison\Users($meta_key)
                    : new ACF\Search\Comparison\User($meta_key);
        }

        return new ACP\Search\Comparison\Meta\Text($meta_key);
    }

}