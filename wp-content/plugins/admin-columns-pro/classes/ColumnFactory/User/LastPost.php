<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\ColumnFactory\User\LastPostFactory;
use AC\Setting\ComponentFactory\PostProperty;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class LastPost extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        LastPostFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    private function get_related_post_type(Config $config): string
    {
        return $config->get('post_type', 'any');
    }

    private function get_related_post_stati(Config $config): ?array
    {
        return (array)$config->get('post_status', []);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\MaxPostDate(
            $this->get_related_post_type($config), $this->get_related_post_stati($config)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\MaxPostDate(
            $this->get_related_post_type($config),
            $this->get_related_post_stati($config)
        );
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        $formatter = null;
        if ($config->get(PostProperty::NAME, '') === PostProperty::PROPERTY_DATE) {
            $post_type = $config->has('post_type') ? (array)$config->get('post_type') : null;
            $post_status = $config->has('post_status') ? (array)$config->get('post_status') : null;

            $formatter = new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new AC\FormatterCollection([
                    new AC\Formatter\User\LastPost($post_type, $post_status),
                    new AC\Formatter\Post\PostDate(),
                ]),
                'Y-m-d H:i:s'
            );
        }

        return new ACP\ConditionalFormat\FormattableConfig($formatter);
    }

}