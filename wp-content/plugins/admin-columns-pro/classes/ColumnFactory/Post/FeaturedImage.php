<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;

class FeaturedImage extends EnhancedColumnFactory
{

    private $post_type;

    public function __construct(
        AC\ColumnFactory\Post\FeaturedImageFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        AC\Type\PostTypeSlug $post_type
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        if ('filesize' === $config->get('featured_image', '')) {
            return null;
        }

        return new Search\Comparison\Post\FeaturedImage((string)$this->post_type);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\FeaturedImage();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\FeaturedImage(),
            new AC\Formatter\Media\AttachmentUrl(),
        ]);
    }

}