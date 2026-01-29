<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\CommentStatus;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Formatter\NetworkSite\CommentCount;
use ACP\Formatter\NetworkSite\SwitchBlog;

class CommentCountFactory extends AdvancedColumnFactory
{

    private CommentStatus $comment_status;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        CommentStatus $comment_status
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->comment_status = $comment_status;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->comment_status->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-msite_commentcount';
    }

    public function get_label(): string
    {
        return __('Comments', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatter = new SwitchBlog([
            new CommentCount($config->get('comment_status', 'approved')),
        ]);

        return FormatterCollection::from_formatter($formatter);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}