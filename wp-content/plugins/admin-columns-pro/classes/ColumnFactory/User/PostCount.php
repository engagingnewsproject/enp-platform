<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting;

class PostCount extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    public function __construct(
        AC\ColumnFactory\User\PostCountFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    private function get_selected_post_types(Config $config): array
    {
        $post_type = $config->get('post_type', 'any');

        if ('any' === $post_type) {
            // All post types, including the ones that are marked "exclude from search"
            return array_keys(get_post_types(['show_ui' => true]));
        }

        if (post_type_exists($post_type)) {
            return [$post_type];
        }

        return [];
    }

    protected function get_selected_post_status(Config $config): array
    {
        $post_status = $config->has('post_status')
            ? (array)$config->get('post_status')
            : [];

        if (empty($post_status)) {
            return get_post_stati(['internal' => 0]);
        }

        return $post_status;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\User\UserPosts(
            $this->get_selected_post_types($config),
            $this->get_selected_post_status($config)
        );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\PostCount(
            $this->get_selected_post_types($config),
            $this->get_selected_post_status($config)
        );
    }

}