<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Attachment extends EnhancedColumnFactory
{

    public function __construct(
        AC\ColumnFactory\Post\AttachmentFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Attachment();
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        // Attachments can only be assigned to a single post
        return parent::get_feature_settings_builder($config)
                     ->set_bulk_edit();
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Post\Attachment();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Post\Attachment();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Attachments(),
            new AC\Formatter\Media\AttachmentUrl(),
        ]);
    }

}