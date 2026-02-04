<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Type\PostTypeSlug;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class PostParent extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private PostTypeSlug $post_type;

    public function __construct(
        AC\ColumnFactory\Post\ParentFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        PostTypeSlug $post_type
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Property('post_parent'),
            new AC\Formatter\Post\PostTitle(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\PostParent((string)$this->post_type);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\PostParent((string)$this->post_type, [(string)$this->post_type]);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\PostParent();
    }

}