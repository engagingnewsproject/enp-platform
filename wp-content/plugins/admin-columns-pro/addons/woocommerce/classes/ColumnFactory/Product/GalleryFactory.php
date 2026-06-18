<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\Collection\Separator;
use AC\Formatter\Media\AttachmentUrl;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\ComponentFactory\NumberOfItems;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class GalleryFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private ImageSize $image_size;

    private NumberOfItems $number_of_items;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image_size,
        NumberOfItems $number_of_items
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image_size = $image_size;
        $this->number_of_items = $number_of_items;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->image_size->create($config))
                     ->add($this->number_of_items->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-product-gallery';
    }

    public function get_label(): string
    {
        return __('Gallery', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Product\GalleryIds())
                     ->add(new Separator('', (int)$config->get('number_of_items', 10)));
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\Product\GalleryIds(),
            new AttachmentUrl(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Gallery())->set_clear_button(true),
            new Editing\Storage\Product\Gallery()
        );
    }
}