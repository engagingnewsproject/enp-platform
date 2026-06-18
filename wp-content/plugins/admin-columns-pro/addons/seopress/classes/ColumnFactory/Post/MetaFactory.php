<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

abstract class MetaFactory extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    protected string $type;

    protected string $label;

    protected string $meta_key;

    private string $group;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        string $meta_key,
        string $group = 'seopress'
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->type = $type;
        $this->label = $label;
        $this->meta_key = $meta_key;
        $this->group = $group;
    }

    protected function get_group(): ?string
    {
        return $this->group;
    }

    public function get_column_type(): string
    {
        return $this->type;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_meta_key(): string
    {
        return $this->meta_key;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\Post\Meta($this->get_meta_key()));
    }

}