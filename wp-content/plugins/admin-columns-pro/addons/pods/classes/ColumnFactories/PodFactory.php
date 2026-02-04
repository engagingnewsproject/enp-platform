<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Type\TableScreenContext;
use AC\Vendor\DI\Container;
use ACA;
use ACA\Pods\ColumnFactory;
use ACA\Pods\FieldFactory;
use ACA\Pods\FieldTypes;
use ACP;
use Pods\Whatsit;

class PodFactory extends AC\ColumnFactories\BaseFactory
{

    private FieldFactory $field_factory;

    public function __construct(Container $container, FieldFactory $field_factory)
    {
        parent::__construct($container);

        $this->field_factory = $field_factory;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        $table_context = TableScreenContext::from_table_screen($table_screen);

        if ( ! $table_context) {
            return $collection;
        }

        switch (true) {
            case $table_screen instanceof AC\TableScreen\Post :
                return $this->get_factories_by_pod_name((string)$table_screen->get_post_type(), $table_context);

            case $table_screen instanceof AC\TableScreen\Media :
                return $this->get_factories_by_pod_name('media', $table_context);

            case $table_screen instanceof AC\TableScreen\User :
                return $this->get_factories_by_pod_name('user', $table_context);

            case $table_screen instanceof AC\TableScreen\Comment :
                return $this->get_factories_by_pod_name('comment', $table_context);

            case $table_screen instanceof ACP\TableScreen\Taxonomy :
                return $this->get_factories_by_pod_name((string)$table_screen->get_taxonomy(), $table_context);

            default:
                return $collection;
        }
    }

    protected function get_factories_by_pod_name(
        $pod_name,
        TableScreenContext $table_context
    ): ColumnFactoryDefinitionCollection {
        add_filter('pods_error_exception', '__return_true', 12); // otherwise pods_error() will throw an exit

        $pod = pods_api()->load_pod(['name' => $pod_name]);

        remove_filter('pods_error_exception', '__return_true', 12);

        return $pod instanceof Whatsit\Pod
            ? $this->create_by_pod($pod, $table_context)
            : new ColumnFactoryDefinitionCollection();
    }

    protected function create_by_pod(
        Whatsit\Pod $pod,
        TableScreenContext $table_context
    ): ColumnFactoryDefinitionCollection {
        $collection = new ColumnFactoryDefinitionCollection();
        $mapping = $this->get_field_mapping();

        foreach ($pod->get_fields() as $pod_field) {
            if ( ! $pod_field instanceof Whatsit\Field) {
                continue;
            }

            if ($pod_field['repeatable']) {
                continue;
            }

            $field = $this->field_factory->create($pod, $pod_field);

            $arguments = [
                'field'         => $field,
                'label'         => $field->get_label(),
                'column_type'   => 'column-pod_' . $field->get_name(),
                'meta_type'     => $table_context->get_meta_type(),
                'post_type'     => $table_context->has_post_type() ? $table_context->get_post_type() : null,
                'taxonomy'      => $table_context->has_taxonomy() ? $table_context->get_taxonomy() : null,
                'table_context' => $table_context,
            ];

            if (array_key_exists($field->get_type(), $mapping)) {
                $collection->add(
                    new ColumnFactoryDefinition(
                        $mapping[$field->get_type()],
                        $arguments
                    )
                );
            }
        }

        return $collection;
    }

    private function get_field_mapping(): array
    {
        return [
            FieldTypes::BOOLEAN             => ColumnFactory\Field\BooleanFactory::class,
            FieldTypes::CODE                => ColumnFactory\Field\CodeFactory::class,
            FieldTypes::COLOR               => ColumnFactory\Field\ColorFactory::class,
            FieldTypes::CURRENCY            => ColumnFactory\Field\CurrencyFactory::class,
            FieldTypes::NUMBER              => ColumnFactory\Field\NumberFactory::class,
            FieldTypes::DATE                => ColumnFactory\Field\DateFactory::class,
            FieldTypes::DATETIME            => ColumnFactory\Field\DateTimeFactory::class,
            FieldTypes::EMAIL               => ColumnFactory\Field\EmailFactory::class,
            FieldTypes::FILE                => ColumnFactory\Field\FileFactory::class,
            FieldTypes::PARAGRAPH           => ColumnFactory\Field\ParagraphFactory::class,
            FieldTypes::PASSWORD            => ColumnFactory\Field\PasswordFactory::class,
            FieldTypes::PHONE               => ColumnFactory\Field\PhoneFactory::class,
            FieldTypes::TEXT                => ColumnFactory\Field\TextFactory::class,
            FieldTypes::TIME                => ColumnFactory\Field\TimeFactory::class,
            FieldTypes::WEBSITE             => ColumnFactory\Field\WebsiteFactory::class,
            FieldTypes::WYSIWYG             => ColumnFactory\Field\WysiwygFactory::class,
            // Pick Columns
            FieldTypes::PICK_CAPABILITY     => ColumnFactory\Field\Pick\CapabilityFactory::class,
            FieldTypes::PICK_COMMENT        => ColumnFactory\Field\Pick\CommentFactory::class,
            FieldTypes::PICK_COUNTRY        => ColumnFactory\Field\Pick\CountryFactory::class,
            FieldTypes::PICK_CUSTOM_SIMPLE  => ColumnFactory\Field\Pick\CustomSimpleFactory::class,
            FieldTypes::PICK_DAYS_OF_WEEK   => ColumnFactory\Field\Pick\DayOfWeekFactory::class,
            FieldTypes::PICK_IMAGE_SIZE     => ColumnFactory\Field\Pick\ImageSizeFactory::class,
            FieldTypes::PICK_MEDIA          => ColumnFactory\Field\Pick\MediaFactory::class,
            FieldTypes::PICK_MONTHS_OF_YEAR => ColumnFactory\Field\Pick\MonthsOfYearFactory::class,
            FieldTypes::PICK_NAV_MENU       => ColumnFactory\Field\Pick\NavMenuFactory::class,
            FieldTypes::PICK_POST_FORMAT    => ColumnFactory\Field\Pick\PostFormatFactory::class,
            FieldTypes::PICK_POST_STATUS    => ColumnFactory\Field\Pick\PostStatusFactory::class,
            FieldTypes::PICK_POST_TYPE      => ColumnFactory\Field\Pick\PostTypeFactory::class,
            FieldTypes::PICK_ROLE           => ColumnFactory\Field\Pick\RoleFactory::class,
            FieldTypes::PICK_TAXONOMY       => ColumnFactory\Field\Pick\TaxonomyFactory::class,
            FieldTypes::PICK_USER           => ColumnFactory\Field\Pick\UserFactory::class,
            FieldTypes::PICK_US_STATE       => ColumnFactory\Field\Pick\UsStatesFactory::class,
        ];
    }

}