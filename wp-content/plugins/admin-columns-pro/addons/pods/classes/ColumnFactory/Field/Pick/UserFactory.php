<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\UserLinkFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use AC\Type\TaxonomySlug;
use ACA\Pods\ColumnFactory\Field\MetaQueryTrait;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Search\Comparison\PickUser;
use ACA\Pods\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\UserProperty;
use ACP\Sorting\FormatValue\SettingFormatter;

class UserFactory extends FieldFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use MetaQueryTrait;

    private UserProperty $user_property;

    private UserLinkFactory $user_link;

    private ?TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        UserProperty $user_property,
        UserLinkFactory $user_link,
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

        $this->user_property = $user_property;
        $this->user_link = $user_link;
        $this->post_type = $field->get_post_type();
        $this->taxonomy = $field->get_taxonomy();
    }

    private function get_user_roles(): array
    {
        return (array)$this->field->get_field()->get_arg('pick_user_role', []);
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->user_property->create($config))
                     ->add($this->user_link->create($this->post_type)->create($config));
    }

    protected function is_multiple(): bool
    {
        return 'multi' === $this->field->get_field()->get_arg('pick_format_type');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $view = (new ACP\Editing\View\AjaxSelect())->set_clear_button(true);
        $storage = new Editing\Storage\Field(
            $this->field,
            new Editing\Storage\Read\DbRaw($this->field->get_name(), $this->field->get_meta_type())
        );

        $args = [];

        $user_roles = $this->get_user_roles();
        if ( ! empty($user_roles)) {
            $args['role__in'] = $user_roles;
        }

        $paginated = new ACP\Editing\PaginatedOptions\Users($args);

        return $this->is_multiple()
            ? new ACP\Editing\Service\Users($view->set_multiple(true), $storage, $paginated)
            : new ACP\Editing\Service\User($view, $storage, $paginated);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        if ( ! $this->is_multiple()) {
            $model = (new ACP\Sorting\Model\RelatedMetaUserFactory())->create(
                $this->field->get_meta_type(),
                (string)$config->get('display_author_as', ''),
                $this->field->get_name()
            );

            if ($model) {
                return $model;
            }
        }

        $setting_formatters = new SettingFormatter($this->user_property->create($config)->get_formatters());

        return (new ACP\Sorting\Model\MetaFormatFactory())->create(
            $this->field->get_meta_type(),
            $this->field->get_name(),
            $setting_formatters,
            null,
            [
                'taxonomy' => (string)$this->taxonomy,
                'post_type' => (string)$this->post_type,
            ]
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new PickUser(
            $this->field->get_name(),
            $this->get_user_roles(),
            $this->get_query_meta($this->get_post_type())
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)
                     ->add(new Value\Formatter\IdCollection('ID'));
    }

}