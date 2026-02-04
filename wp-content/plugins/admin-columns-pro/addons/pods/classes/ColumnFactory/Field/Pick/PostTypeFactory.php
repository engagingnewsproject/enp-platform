<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\PostLink;
use AC\Setting\ComponentFactory\PostProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\Field\MetaQueryTrait;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Search\Comparison\PickPost;
use ACA\Pods\Value\Formatter\IdCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting\Model\RelatedMetaPostFactory;

class PostTypeFactory extends FieldFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use MetaQueryTrait;

    private PostProperty $post_property;

    private PostLink $post_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        PostProperty $post_property,
        PostLink $post_link,
        TableScreenContext $table_context
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $table_context
        );
        $this->post_property = $post_property;
        $this->post_link = $post_link;
    }

    private function get_related_post_type(): string
    {
        return (string)$this->field->get_field()->get_arg('pick_val', '');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->post_property->create($config))
                     ->add($this->post_link->create($config));
    }

    protected function is_multiple(): bool
    {
        return 'multi' === $this->field->get_field()->get_arg('pick_format_type');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $args = [];

        $status = $this->field->get_field()->get_arg('pick_post_status', '');

        if ($status) {
            $args['post_status'] = (array)$status;
        }

        $related_post_type = $this->get_related_post_type();

        $paginated = new ACP\Editing\PaginatedOptions\Posts(
            $related_post_type
                ? [$related_post_type]
                : [],
            $args
        );

        $storage = new Editing\Storage\Field(
            $this->field,
            new Editing\Storage\Read\DbRaw($this->field->get_name(), $this->field->get_meta_type())
        );

        return $this->is_multiple()
            ? new ACP\Editing\Service\Posts(
                (new ACP\Editing\View\AjaxSelect())->set_clear_button(true)->set_multiple(true),
                $storage,
                $paginated
            )
            : new ACP\Editing\Service\Post(
                (new ACP\Editing\View\AjaxSelect())->set_clear_button(true),
                $storage,
                $paginated
            );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        if ($this->is_multiple()) {
            return null;
        }

        return (new RelatedMetaPostFactory())->create(
            $this->field->get_meta_type(),
            $config->get(PostProperty::NAME, ''),
            $this->field->get_name()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new PickPost(
            (string)$this->field->get_name(),
            (array)$this->field->get_field()->get_arg('pick_val', ''),
            $this->get_query_meta($this->get_post_type())
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)->add(new IdCollection());
    }

}