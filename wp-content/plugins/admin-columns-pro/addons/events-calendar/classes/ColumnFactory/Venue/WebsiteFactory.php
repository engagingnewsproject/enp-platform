<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Venue;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC\ColumnFactory\MetaTextFieldFactory;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class WebsiteFactory extends MetaTextFieldFactory
{

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            'column-ec-venue_website',
            __('Website', 'codepress-admin-columns'),
            '_VenueURL'
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new AC\Formatter\Linkable(null, '_blank '));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Url())->set_clear_button(true),
            new ACP\Editing\Storage\Post\Meta($this->meta_key)
        );
    }

}