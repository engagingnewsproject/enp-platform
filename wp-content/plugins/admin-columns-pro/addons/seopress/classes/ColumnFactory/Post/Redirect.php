<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\Value;
use ACP\Column\FeatureSettingBuilderFactory;

final class Redirect extends MetaBooleanFactory
{

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder
    ) {
        parent::__construct(
            $feature_setting_builder_factory,
            $default_settings_builder,
            'column-sp_redirect',
            __('Redirect?', 'wp-seopress-pro'),
            '_seopress_redirections_enabled'
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(
                         new AC\Formatter\FallBackFormatter(
                             new AC\Formatter\ConditionalValue(
                                 new Value('yes'),
                                 new AC\Formatter\YesIcon()
                             ),
                             new AC\Formatter\NoIcon()
                         )
                     );
    }

}