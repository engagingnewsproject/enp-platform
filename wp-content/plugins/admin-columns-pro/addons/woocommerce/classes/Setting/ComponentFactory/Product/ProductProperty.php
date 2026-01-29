<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Product;

use AC\Expression\StringComparisonSpecification;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Children;
use AC\Setting\ComponentFactory\CustomFieldFactory;
use AC\Setting\ComponentFactory\DateFormat\Date;
use AC\Setting\ComponentFactory\FieldTypeBasic;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\ComponentFactory\PostProperty;
use AC\Setting\ComponentFactory\PostStatusIcon;
use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;
use AC\Type\PostTypeSlug;
use AC\Type\TableScreenContext;
use ACA\WC\Value\Formatter\Fallback;

class ProductProperty extends PostProperty
{

    public const TYPE_SKU = 'sku';
    public const TYPE_META = 'custom_field';

    private CustomFieldFactory $custom_field;

    private FieldTypeBasic $custom_field_type;

    public function __construct(
        StringLimit $string_limit,
        ImageSize $image_size,
        UserProperty $user_property,
        PostStatusIcon $post_status_icon,
        Date $date,
        CustomFieldFactory $custom_field,
        FieldTypeBasic $custom_field_type
    ) {
        parent::__construct($string_limit, $image_size, $user_property, $post_status_icon, $date);

        $this->custom_field = $custom_field;
        $this->custom_field_type = $custom_field_type;
    }

    protected function get_label(Config $config): string
    {
        return __('Product Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): Input
    {
        return OptionFactory::create_select(
            'product_property',
            $this->get_display_options(),
            $config->get('product_property', self::PROPERTY_TITLE)
        );
    }

    protected function get_children(Config $config): ?Children
    {
        $components = parent::get_children($config)->get_iterator();

        $table_screen_context = new TableScreenContext(MetaType::create_post_meta(), new PostTypeSlug('product'));

        $components->add(
            $this->custom_field->create($table_screen_context)->create(
                $config,
                StringComparisonSpecification::equal('custom_field')
            )
        );
        $components->add(
            $this->custom_field_type->create(
                $config,
                StringComparisonSpecification::equal('custom_field')
            )
        );

        return new Children($components);
    }

    protected function get_display_options(): OptionCollection
    {
        $options = new OptionCollection([]);

        // Set group to 'Post'
        foreach (parent::get_display_options() as $option) {
            $options->add(
                new Option(
                    $option->get_label(),
                    $option->get_value(),
                    __('Post')
                )
            );
        }

        $options->add(
            new Option(
                __('SKU', 'woocommerce'),
                self::TYPE_SKU,
                __('Product', 'codepress-admin-columns')
            )
        );
        $options->add(
            new Option(
                __('Image', 'woocommerce'),
                self::PROPERTY_FEATURED_IMAGE,
                __('Product', 'codepress-admin-columns')
            )
        );
        $options->add(
            new Option(
                __('Custom Field', 'woocommerce'),
                self::TYPE_META,
                __('Product', 'codepress-admin-columns')
            )
        );

        return $options;
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get('product_property')) {
            case self::TYPE_SKU:
                $formatters->add(new Meta('_sku'));
                $formatters->add(new Fallback());
                break;
            case self::TYPE_META:
                $field = $config->get('field');

                if ($field) {
                    $formatters->add(new Meta((string)$field));
                }
                break;
            default:
                parent::add_formatters($config, $formatters);
        }
    }

}