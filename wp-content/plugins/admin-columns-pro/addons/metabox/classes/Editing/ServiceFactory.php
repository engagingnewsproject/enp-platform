<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing;

use AC\Helper\Select\Option;
use AC\Type\TableScreenContext;
use AC\Type\ToggleOptions;
use ACA\MetaBox\Editing;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACP;

class ServiceFactory
{

    private StorageFactory $storage_factory;

    public function __construct(StorageFactory $storage_factory)
    {
        $this->storage_factory = $storage_factory;
    }

    public function create(Field\Field $field, TableScreenContext $table_context): ?ACP\Editing\Service
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::AUTOCOMPLETE:
                if ( ! $field instanceof Field\Type\AutoComplete) {
                    return null;
                }

                $view = new ACP\Editing\View\AdvancedSelect($field->get_choices());

                return $field->is_ajax() || $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        $view->set_clear_button(true)->set_multiple(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
            case MetaboxFieldTypes::CHECKBOX:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\Toggle(new ToggleOptions(new Option(''), new Option('1')))),
                        $this->storage_factory->create($field, $table_context)
                    );
            case MetaboxFieldTypes::CHECKBOX_LIST:
                $options = $field instanceof Field\Choices ? $field->get_choices() : [];

                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\CheckboxList($options))->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
            case MetaboxFieldTypes::DATE:
                if ($field->is_cloneable() || ! $field instanceof Field\DateFormat) {
                    return null;
                }

                return new ACP\Editing\Service\Date(
                    (new ACP\Editing\View\Date())->set_clear_button(true),
                    $this->storage_factory->create($field, $table_context),
                    $field->get_date_format()
                );

            case MetaboxFieldTypes::DATETIME:
                return $field->is_cloneable() || ! $field instanceof Field\DateFormat
                    ? null
                    : new ACP\Editing\Service\DateTime(
                        (new ACP\Editing\View\DateTime())->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context),
                        $field->get_date_format()
                    );
            case MetaboxFieldTypes::IMAGE_SELECT:
                $options = $field instanceof Field\Choices ? array_keys($field->get_choices()) : [];

                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\AdvancedSelect(array_combine($options, $options)))->set_clear_button(
                            true
                        ),
                        $this->storage_factory->create($field, $table_context, false)
                    );

            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::SLIDER:
            case MetaboxFieldTypes::RANGE:
                $view = $field->is_cloneable()
                    ? (new ACP\Editing\View\MultiInput())->set_sub_type('number')->set_clear_button(true)
                    : (new ACP\Editing\View\Number())->set_clear_button(true);

                if ($view instanceof ACP\Editing\View\Number && $field instanceof Field\Numeric) {
                    $view->set_min($field->get_min());
                    $view->set_max($field->get_max());
                    $view->set_step($field->get_step());
                }

                return new ACP\Editing\Service\Basic(
                    $view,
                    $this->storage_factory->create($field, $table_context)
                );
            case MetaboxFieldTypes::RADIO:
                $options = $field instanceof Field\Choices ? $field->get_choices() : [];

                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\Select($options))->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::SELECT_ADVANCED:
                $options = $field instanceof Field\Choices ? $field->get_choices() : [];
                $view = new ACP\Editing\View\AdvancedSelect($options);

                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        $view
                            ->set_clear_button(true)
                            ->set_multiple(
                                $field instanceof Field\Multiple && $field->is_multiple()
                            ),
                        $this->storage_factory->create($field, $table_context, false)
                    );

            case MetaboxFieldTypes::EMAIL:
            case MetaboxFieldTypes::PASSWORD:
            case MetaboxFieldTypes::COLORPICKER:
            case MetaboxFieldTypes::URL:
            case MetaboxFieldTypes::OEMBED:
            case MetaboxFieldTypes::TEXT:
            case MetaboxFieldTypes::TEXTAREA:
            case MetaboxFieldTypes::TIME:
            case MetaboxFieldTypes::WYSIWYG:
                return new ACP\Editing\Service\Basic(
                    (new InputViewFactory())->create($field),
                    $this->storage_factory->create($field, $table_context)
                );

            case MetaboxFieldTypes::POST:
                if ( ! $field instanceof Field\Type\Post || $field->is_cloneable()) {
                    return null;
                }

                return $field->is_multiple()
                    ? new ACP\Editing\Service\Posts(
                        (new ACP\Editing\View\AjaxSelect())->set_clear_button(true)->set_multiple(true),
                        $this->storage_factory->create($field, $table_context, false),
                        new ACP\Editing\PaginatedOptions\Posts(
                            $field->get_post_types(),
                            $field->get_query_args()
                        )
                    )
                    : new ACP\Editing\Service\Post(
                        (new ACP\Editing\View\AjaxSelect())->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context),
                        new ACP\Editing\PaginatedOptions\Posts(
                            $field->get_post_types(),
                            $field->get_query_args()
                        )
                    );
            case MetaboxFieldTypes::TAXONOMY:
                if ( ! $field instanceof Field\Type\Taxonomy || $field->is_cloneable()) {
                    return null;
                }

                $taxonomies = $field->get_taxonomies();
                $storage = new Storage\TermField(
                    $field->get_id(),
                    $table_context->get_meta_type(),
                    $field->get_settings(),
                    ! $field->is_multiple()
                );

                return $field->is_multiple()
                    ? new Editing\Service\Taxonomies($storage, $taxonomies)
                    : new Editing\Service\Taxonomy($storage, $taxonomies);
            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
                if ( ! $field instanceof Field\Type\TaxonomyAdvanced || $field->is_cloneable()) {
                    return null;
                }

                $taxonomies = $field->get_taxonomies();
                $storage = $this->storage_factory->create($field, $table_context, ! $field->is_multiple());

                return $field->is_multiple()
                    ? new Editing\Service\TaxonomiesAdvanced($storage, $taxonomies)
                    : new Editing\Service\Taxonomy($storage, $taxonomies);
            case MetaboxFieldTypes::USER:
                if ( ! $field instanceof Field\Type\User || $field->is_cloneable()) {
                    return null;
                }

                $view = (new ACP\Editing\View\AjaxSelect())
                    ->set_multiple($field->is_multiple())
                    ->set_clear_button(true);
                $storage = $this->storage_factory->create($field, $table_context, ! $field->is_multiple());
                $paginated = new ACP\Editing\PaginatedOptions\Users($field->get_query_args());

                return $field->is_multiple()
                    ? new ACP\Editing\Service\Users($view, $storage, $paginated)
                    : new ACP\Editing\Service\User($view, $storage, $paginated);
            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_UPLOAD:
                $view = (new ACP\Editing\View\Media())
                    ->set_clear_button(true)
                    ->set_multiple($field instanceof Field\Multiple && $field->is_multiple());

                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic($view, $this->storage_factory->create($field, $table_context));
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\Gallery())->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
            case MetaboxFieldTypes::VIDEO:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\Video())->set_clear_button(true)->set_multiple(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
            case MetaboxFieldTypes::SINGLE_IMAGE:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Editing\Service\Basic(
                        (new ACP\Editing\View\Image())->set_clear_button(true),
                        $this->storage_factory->create($field, $table_context, false)
                    );
        }

        return null;
    }

}