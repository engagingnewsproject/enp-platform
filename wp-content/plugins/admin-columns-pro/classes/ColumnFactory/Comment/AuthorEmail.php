<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class AuthorEmail extends ACP\Column\EnhancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Comment\AuthorEmailFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Email(),
            new Editing\Storage\Comment\Field('comment_author_email')
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Comment\OrderByNonUnique('comment_author_email');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Email();
    }

}