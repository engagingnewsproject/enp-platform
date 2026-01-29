<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\IsLinkable;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class MetaUrlFactory extends MetaFactory
{

    private IsLinkable $is_linkable;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        string $meta_key,
        IsLinkable $is_linkable
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder, $type, $label, $meta_key);
        $this->is_linkable = $is_linkable;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->is_linkable->create($config));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Url(),
            new ACP\Editing\Storage\Post\Meta($this->get_meta_key())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($this->get_meta_key());
    }

}