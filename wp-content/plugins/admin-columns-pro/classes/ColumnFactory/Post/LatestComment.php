<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Formatter\Comment\MetaDateAndAuthor;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\CommentDisplay;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Formatter\Post\LatestCommentId;
use ACP\Sorting;

class LatestComment extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private CommentDisplay $comment_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        CommentDisplay $comment_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->comment_display = $comment_display;
    }

    public function get_label(): string
    {
        return __('Latest Comment', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-latest_comment';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->prepend(new LatestCommentId());
        $formatters->add(new MetaDateAndAuthor());

        return $formatters;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->comment_display->create($config),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\LatestComment();
    }

}